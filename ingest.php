<?php
header('Content-Type: application/json');
require __DIR__.'/db.php';

function cap_rows_per_device(PDO $pdo, string $deviceId, int $keep = 1000): int {
  $sql = "
    DELETE FROM readings
    WHERE device_id = ?
      AND id < (
        SELECT cutoff_id FROM (
          SELECT id AS cutoff_id
          FROM readings
          WHERE device_id = ?
          ORDER BY id DESC
          LIMIT 1 OFFSET ?
        ) AS x
      )
  ";
  $st = $pdo->prepare($sql);
  $st->execute([$deviceId, $deviceId, max(0, $keep - 1)]);
  return $st->rowCount();
}


$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { http_response_code(400); echo json_encode(['ok'=>false,'err'=>'bad json']); exit; }


$device_id = isset($input['device_id']) ? $input['device_id'] : '';
$api_key = isset($input['api_key']) ? $input['api_key'] : '';
$voltage = isset($input['voltage']) ? $input['voltage'] : null;
$current = isset($input['current_ma']) ? $input['current_ma'] : null;
$power = isset($input['power_w']) ? $input['power_w'] : null;
$energy = isset($input['energy_kwh_current']) ? $input['energy_kwh_current'] : null;


if (!$device_id || !$api_key) { http_response_code(401); echo json_encode(['ok'=>false,'err'=>'missing id/key']); exit; }


// simple auth: check api_key matches device
$stmt = $pdo->prepare('SELECT api_key FROM devices WHERE device_id = ?');
$stmt->execute([$device_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row || !hash_equals($row['api_key'], $api_key)) { http_response_code(403); echo json_encode(['ok'=>false,'err'=>'auth']); exit; }


// insert reading

$stmt = $pdo->prepare('INSERT INTO readings (device_id, voltage, current_ma, power_w, energy_kwh_current, energy_kwh_total) VALUES (?,?,?,?,?,?)');

// Parse reported values
$energy_total_reported = array_key_exists('energy_kwh_total', $input) ? (float)$input['energy_kwh_total'] : null;
$energy_current_reported = array_key_exists('energy_kwh_current', $input) ? (float)$input['energy_kwh_current'] : null;

// Fetch previous total (if any) to compute delta
$stmtPrev = $pdo->prepare('SELECT energy_kwh_total FROM readings WHERE device_id = ? ORDER BY ts DESC LIMIT 1');
$stmtPrev->execute([$device_id]);
$prev_total = $stmtPrev->fetchColumn(); // false if none

// Compute energy_kwh_current (the incremental energy since previous row)
if ($energy_current_reported !== null) {
	// Device provided an explicit current/delta value. Use it, but guard against obviously wrong values.
	$energy_kwh_current = max(0.0, (float)$energy_current_reported);
} elseif ($energy_total_reported !== null && $prev_total !== false) {
	// Compute delta between reported cumulative total and previous stored total
	$delta = (float)$energy_total_reported - (float)$prev_total;
	$energy_kwh_current = ($delta > 0) ? (float)$delta : 0.0;
} else {
	// No reliable info: default to 0.0
	$energy_kwh_current = 0.0;
}

// Decide what to store as the cumulative total in DB
if ($energy_total_reported !== null) {
	$energy_kwh_total = (float)$energy_total_reported;
} elseif ($prev_total !== false) {
	$energy_kwh_total = (float)$prev_total + (float)$energy_kwh_current;
} else {
	$energy_kwh_total = (float)$energy_kwh_current; // first row or no prev total
}

// Defensive correction: ensure total is at least as large as previous total
if ($prev_total !== false && $energy_kwh_total < (float)$prev_total) {
	// avoid regressions in cumulative total; keep previous total
	$energy_kwh_total = (float)$prev_total;
}

// Optional debug logging to help diagnose device payloads (do not log api_key)
$logLine = sprintf("%s | device=%s | prev_total=%s | reported_total=%s | reported_current=%s | computed_current=%s | store_total=%s\n",
	date('c'), $device_id, var_export($prev_total, true), var_export($energy_total_reported, true), var_export($energy_current_reported, true), var_export($energy_kwh_current, true), var_export($energy_kwh_total, true)
);
@file_put_contents(__DIR__ . '/ingest_debug.log', $logLine, FILE_APPEND | LOCK_EX);

try{
	$stmt->execute([$device_id, $voltage, $current, $power, $energy_kwh_current, $energy_kwh_total]);
}catch(PDOException $e){
	// Return a clean JSON error instead of a fatal error dump
	http_response_code(500);
	echo json_encode(['ok'=>false,'err'=>'db_error','msg'=>$e->getMessage()]);
	exit;
}


echo json_encode(['ok'=>true]);