<?php
header('Content-Type: application/json');
require __DIR__.'/db.php';


$device_id = $_GET['device_id'] ?? '';
$minutes = max(1, min(1440, intval($_GET['minutes'] ?? 60))); // 1..1440
if (!$device_id) { http_response_code(400); echo json_encode(['ok'=>false,'err'=>'device_id']); exit; }


$sql = "SELECT ts, voltage, current_ma, power_w, energy_kwh_current FROM readings WHERE device_id = :device_id AND ts >= (NOW() - INTERVAL :minutes MINUTE) ORDER BY ts ASC";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':device_id', $device_id);
$stmt->bindValue(':minutes', $minutes, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);


echo json_encode(['ok'=>true,'rows'=>$rows]);