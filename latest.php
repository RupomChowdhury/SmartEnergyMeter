<?php
header('Content-Type: application/json');
require __DIR__.'/db.php';


$device_id = $_GET['device_id'] ?? '';
if (!$device_id) { http_response_code(400); echo json_encode(['ok'=>false,'err'=>'device_id']); exit; }


$sql = "SELECT ts, voltage, current_ma, power_w, energy_kwh_current, energy_kwh_total,
	(COALESCE(energy_kwh_total,0) + COALESCE(energy_kwh_current,0)) AS energy_kwh_display
	FROM readings WHERE device_id = ? ORDER BY ts DESC LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([$device_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);


echo json_encode(['ok'=>true,'row'=>$row]);