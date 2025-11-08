<?php
header('Content-Type: application/json');
require __DIR__.'/db.php';


$device_id = $_GET['device_id'] ?? '';
if (!$device_id) { http_response_code(400); echo json_encode(['ok'=>false,'err'=>'device_id']); exit; }


$sql = <<<SQL
WITH per_row AS (
SELECT r.*, LAG(energy_kwh_current) OVER (PARTITION BY r.device_id ORDER BY r.ts) AS prev_total
FROM readings r WHERE r.device_id = :device_id
), daily AS (
SELECT DATE(ts) AS day,
GREATEST(SUM(GREATEST(energy_kwh_current - prev_total, 0)), 0) AS kwh
FROM per_row
GROUP BY DATE(ts)
)
SELECT d.day,
d.kwh,
t.rate_per_kwh,
(d.kwh * t.rate_per_kwh) AS cost
FROM daily d
JOIN device_tariff dt ON dt.device_id = :device_id
JOIN tariffs t ON t.id = dt.tariff_id
WHERE (t.effective_to IS NULL OR d.day <= t.effective_to)
AND d.day >= t.effective_from
ORDER BY d.day DESC
SQL;


$stmt = $pdo->prepare($sql);
$stmt->execute(['device_id'=>$device_id]);
echo json_encode(['ok'=>true,'rows'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);