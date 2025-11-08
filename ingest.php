<?php
header('Content-Type: application/json');
require __DIR__ . '/db.php';

function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function parseNumeric($value): ?float
{
    if ($value === null || $value === '' || !is_numeric($value)) {
        return null;
    }
    return (float) $value;
}

function parseTimestamp(?string $raw): DateTimeImmutable
{
    $tz = new DateTimeZone('UTC');
    if ($raw === null || $raw === '') {
        return new DateTimeImmutable('now', $tz);
    }
    try {
        $dt = new DateTimeImmutable($raw, $tz);
    } catch (Exception $e) {
        return new DateTimeImmutable('now', $tz);
    }
    return $dt->setTimezone($tz);
}

function findActiveTariff(PDO $pdo, string $deviceId, string $day): ?array
{
    $sql = 'SELECT t.id, t.rate_per_kwh, t.fixed_monthly, t.currency
            FROM tariffs t
            INNER JOIN device_tariff dt ON dt.tariff_id = t.id
            WHERE dt.device_id = :device_id
              AND t.effective_from <= :day
              AND (t.effective_to IS NULL OR t.effective_to >= :day)
            ORDER BY t.effective_from DESC
            LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['device_id' => $deviceId, 'day' => $day]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function updateDailyUsage(PDO $pdo, string $deviceId, string $day, float $deltaKwh, array $tariff): void
{
    $sql = 'INSERT INTO daily_usage (device_id, `day`, tariff_id, rate_per_kwh, currency, energy_kwh, energy_cost)
            VALUES (:device_id, :day, :tariff_id, :rate, :currency, :kwh, :cost)
            ON DUPLICATE KEY UPDATE
              tariff_id = VALUES(tariff_id),
              rate_per_kwh = VALUES(rate_per_kwh),
              currency = VALUES(currency),
              energy_kwh = energy_kwh + VALUES(energy_kwh),
              energy_cost = energy_cost + VALUES(energy_cost),
              updated_at = CURRENT_TIMESTAMP';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'device_id' => $deviceId,
        'day' => $day,
        'tariff_id' => $tariff['id'] ?? null,
        'rate' => $tariff['rate_per_kwh'] ?? 0.0,
        'currency' => $tariff['currency'] ?? 'BDT',
        'kwh' => $deltaKwh,
        'cost' => $deltaKwh * (float) ($tariff['rate_per_kwh'] ?? 0.0),
    ]);
}

function updateMonthlyUsage(PDO $pdo, string $deviceId, string $monthStart, float $deltaKwh, array $tariff): void
{
    $fixedApplied = ((float) $tariff['fixed_monthly']) > 0 ? 1 : 0;
    $sql = 'INSERT INTO monthly_usage (device_id, `month`, tariff_id, rate_per_kwh, fixed_monthly, currency, energy_kwh, energy_cost_variable, fixed_charge_applied)
            VALUES (:device_id, :month, :tariff_id, :rate, :fixed, :currency, :kwh, :variable_cost, :fixed_applied)
            ON DUPLICATE KEY UPDATE
              tariff_id = VALUES(tariff_id),
              rate_per_kwh = VALUES(rate_per_kwh),
              fixed_monthly = VALUES(fixed_monthly),
              currency = VALUES(currency),
              energy_kwh = energy_kwh + VALUES(energy_kwh),
              energy_cost_variable = energy_cost_variable + VALUES(energy_cost_variable),
              fixed_charge_applied = GREATEST(fixed_charge_applied, VALUES(fixed_charge_applied)),
              updated_at = CURRENT_TIMESTAMP';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'device_id' => $deviceId,
        'month' => $monthStart,
        'tariff_id' => $tariff['id'] ?? null,
        'rate' => $tariff['rate_per_kwh'] ?? 0.0,
        'fixed' => $tariff['fixed_monthly'] ?? 0.0,
        'currency' => $tariff['currency'] ?? 'BDT',
        'kwh' => $deltaKwh,
        'variable_cost' => $deltaKwh * (float) ($tariff['rate_per_kwh'] ?? 0.0),
        'fixed_applied' => $fixedApplied,
    ]);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    respond(400, ['ok' => false, 'err' => 'bad json']);
}

$deviceId = $input['device_id'] ?? '';
$apiKey = $input['api_key'] ?? '';
$voltage = parseNumeric($input['voltage'] ?? null);
$current = parseNumeric($input['current_ma'] ?? null);
$power = parseNumeric($input['power_w'] ?? null);

if ($deviceId === '' || $apiKey === '') {
    respond(401, ['ok' => false, 'err' => 'missing id/key']);
}

// simple auth: check api_key matches device
$stmt = $pdo->prepare('SELECT api_key FROM devices WHERE device_id = ?');
$stmt->execute([$deviceId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row || !hash_equals($row['api_key'], $apiKey)) {
    respond(403, ['ok' => false, 'err' => 'auth']);
}

$ts = parseTimestamp($input['ts'] ?? null);
$tsString = $ts->format('Y-m-d H:i:s');
$dayString = $ts->format('Y-m-d');
$monthString = $ts->format('Y-m-01');

$energyTotalReported = parseNumeric($input['energy_kwh_total'] ?? null);
$energyCurrentReported = parseNumeric($input['energy_kwh_current'] ?? null);

$prevTotal = null;
$prevTs = null;
$prevPower = null;
$energyKwhCurrent = 0.0;
$energyKwhTotal = 0.0;

$voltage = $voltage ?? 0.0;
$current = $current ?? 0.0;
$power = $power ?? 0.0;

try {
    $pdo->beginTransaction();

    $stmtPrev = $pdo->prepare('SELECT ts, power_w, energy_kwh_total FROM readings WHERE device_id = ? ORDER BY ts DESC LIMIT 1 FOR UPDATE');
    $stmtPrev->execute([$deviceId]);
    $prevRow = $stmtPrev->fetch(PDO::FETCH_ASSOC);

    if ($prevRow) {
        $prevTotal = (float) $prevRow['energy_kwh_total'];
        $prevPower = max(0.0, (float) $prevRow['power_w']);
        try {
            $prevTs = new DateTimeImmutable($prevRow['ts'], new DateTimeZone('UTC'));
        } catch (Exception $e) {
            $prevTs = null;
        }
    }

    $deltaSeconds = 0;
    if ($prevTs instanceof DateTimeImmutable) {
        $deltaSeconds = max(0, $ts->getTimestamp() - $prevTs->getTimestamp());
        if ($deltaSeconds === 0) {
            $deltaSeconds = 1; // minimum integration window
        } elseif ($deltaSeconds > 900) {
            $deltaSeconds = 900; // cap at 15 minutes to avoid runaway when device pauses
        }
    }

    $currPower = max(0.0, $power);
    $prevPower = $prevPower ?? $currPower;
    $avgPowerKw = ($currPower + $prevPower) / 2000.0; // convert to kW and average
    $deltaFromPower = ($deltaSeconds > 0) ? $avgPowerKw * ($deltaSeconds / 3600.0) : 0.0;

    $deltaFromCurrent = null;
    if ($energyCurrentReported !== null) {
        $deltaFromCurrent = max(0.0, $energyCurrentReported);
    }

    if ($deltaFromCurrent !== null && $deltaFromCurrent > 0 && $deltaFromPower > 0) {
        $ratio = $deltaFromCurrent / $deltaFromPower;
        if ($ratio > 50) {
            // Firmware reports watt-hours, normalise to kWh.
            $deltaFromCurrent /= 1000.0;
        } elseif ($ratio < 0.02) {
            // Extremely tiny ratios likely milli-watt hours, scale up.
            $deltaFromCurrent *= 1000.0;
        }
    }

    $deltaFromTotal = null;
    if ($energyTotalReported !== null && $prevTotal !== null) {
        $candidate = $energyTotalReported - $prevTotal;
        if ($candidate >= 0) {
            $deltaFromTotal = $candidate;
        }
    }

    $energyKwhCurrent = 0.0;
    if ($deltaFromTotal !== null) {
        $energyKwhCurrent = $deltaFromTotal;
        $energyKwhTotal = max($energyTotalReported, $prevTotal + $energyKwhCurrent);
    } else {
        $energyKwhCurrent = $deltaFromCurrent ?? $deltaFromPower;
        if ($energyKwhCurrent < 0) {
            $energyKwhCurrent = 0.0;
        }
        $baseTotal = $prevTotal ?? 0.0;
        $energyKwhTotal = $baseTotal + $energyKwhCurrent;
    }

    if ($energyTotalReported !== null && $prevTotal !== null && $energyTotalReported < $prevTotal) {
        $energyKwhCurrent = max($energyKwhCurrent, max(0.0, $energyTotalReported));
        $energyKwhTotal = $prevTotal + $energyKwhCurrent;
    }

    if ($energyTotalReported !== null && $prevTotal === null) {
        $energyKwhCurrent = max($energyKwhCurrent, $energyTotalReported);
        $energyKwhTotal = max($energyKwhCurrent, $energyTotalReported);
    }

    if ($prevTotal !== null && $energyKwhTotal < $prevTotal) {
        $energyKwhTotal = $prevTotal;
    }

    if ($energyKwhCurrent < 1e-9) {
        $energyKwhCurrent = 0.0;
        if ($prevTotal !== null) {
            $energyKwhTotal = $prevTotal;
        }
    }

    $stmtInsert = $pdo->prepare('INSERT INTO readings (device_id, ts, voltage, current_ma, power_w, energy_kwh_current, energy_kwh_total)
                                  VALUES (?,?,?,?,?,?,?)');
    $stmtInsert->execute([
        $deviceId,
        $tsString,
        $voltage,
        $current,
        $power,
        $energyKwhCurrent,
        $energyKwhTotal,
    ]);

    if ($energyKwhCurrent > 0) {
        $tariff = findActiveTariff($pdo, $deviceId, $dayString) ?? [
            'id' => null,
            'rate_per_kwh' => 0.0,
            'fixed_monthly' => 0.0,
            'currency' => 'BDT',
        ];
        updateDailyUsage($pdo, $deviceId, $dayString, $energyKwhCurrent, $tariff);
        updateMonthlyUsage($pdo, $deviceId, $monthString, $energyKwhCurrent, $tariff);
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    respond(500, ['ok' => false, 'err' => 'db_error', 'msg' => $e->getMessage()]);
}

$logLine = sprintf(
    "%s | device=%s | prev_total=%s | reported_total=%s | reported_current=%s | delta_from_power=%s | seconds=%d | stored_current=%s | store_total=%s\n",
    date('c'),
    $deviceId,
    var_export($prevTotal ?? null, true),
    var_export($energyTotalReported, true),
    var_export($energyCurrentReported, true),
    var_export($deltaFromPower, true),
    $deltaSeconds,
    var_export($energyKwhCurrent, true),
    var_export($energyKwhTotal, true)
);
@file_put_contents(__DIR__ . '/ingest_debug.log', $logLine, FILE_APPEND | LOCK_EX);

respond(200, ['ok' => true]);
