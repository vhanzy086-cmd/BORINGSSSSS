<?php
header('Content-Type: application/json');
require_once '../config.php';

$pdo = getDB();
$stmt = $pdo->query("SELECT id, title, artist, file_path, thumbnail, duration FROM music ORDER BY plays DESC");
$songs = $stmt->fetchAll();

echo json_encode($songs);
?>