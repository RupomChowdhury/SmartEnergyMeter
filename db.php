<?php
$dsn = 'mysql:host=localhost;dbname=smart_energy;charset=utf8mb4';
$user = 'root';
$pass = '';
$options = [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ];
$pdo = new PDO($dsn, $user, $pass, $options);
?>