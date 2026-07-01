<?php
header('Content-Type: application/json');
require_once '../config.php';

$pdo = getDB();

// Total users
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
$total_users = $stmt->fetch()['count'];

// Active users (logged in within last 7 days)
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE last_login > DATE_SUB(NOW(), INTERVAL 7 DAY)");
$active_users = $stmt->fetch()['count'];

// Total keys
$stmt = $pdo->query("SELECT COUNT(*) as count FROM keys_table");
$total_keys = $stmt->fetch()['count'];

// Total songs
$stmt = $pdo->query("SELECT COUNT(*) as count FROM music");
$total_songs = $stmt->fetch()['count'];

echo json_encode([
    'total_users' => (int)$total_users,
    'active_users' => (int)$active_users,
    'total_keys' => (int)$total_keys,
    'total_songs' => (int)$total_songs
]);
?>