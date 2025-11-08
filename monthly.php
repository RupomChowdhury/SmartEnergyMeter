<?php
header('Content-Type: application/json');
require __DIR__.'/db.php';


$device_id = $_GET['device_id'] ?? '';
if (!$device_id) { http_response_code(400); echo json_encode(['ok'=>false,'err'=>'device_id']); exit; }


$sql = <<<SQL
SELECT `month` AS month_start,
       energy_kwh AS kwh,
       rate_per_kwh,
       fixed_monthly,
       energy_cost_variable AS variable_cost,
       (CASE WHEN fixed_charge_applied = 1 THEN fixed_monthly ELSE 0 END) AS fixed_cost,
       (energy_cost_variable + CASE WHEN fixed_charge_applied = 1 THEN fixed_monthly ELSE 0 END) AS total_cost,
       currency
FROM monthly_usage
WHERE device_id = :device_id
ORDER BY `month` DESC
LIMIT 36
SQL;


$stmt = $pdo->prepare($sql);
$stmt->execute(['device_id'=>$device_id]);
echo json_encode(['ok'=>true,'rows'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);