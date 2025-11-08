<?php
header('Content-Type: application/json');
require __DIR__.'/db.php';


$device_id = $_GET['device_id'] ?? '';
if (!$device_id) { http_response_code(400); echo json_encode(['ok'=>false,'err'=>'device_id']); exit; }


$sql = <<<SQL
SELECT `day`,
       energy_kwh AS kwh,
       rate_per_kwh,
       energy_cost AS cost,
       currency
FROM daily_usage
WHERE device_id = :device_id
ORDER BY `day` DESC
LIMIT 90
SQL;


$stmt = $pdo->prepare($sql);
$stmt->execute(['device_id'=>$device_id]);
echo json_encode(['ok'=>true,'rows'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);