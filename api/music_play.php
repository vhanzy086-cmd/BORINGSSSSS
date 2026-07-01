<?php
header('Content-Type: application/json');
require_once '../config.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$music_id = $data['music_id'] ?? 0;

if (!$music_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid music ID']);
    exit();
}

$pdo = getDB();

// Update plays
$stmt = $pdo->prepare("UPDATE music SET plays = plays + 1 WHERE id = ?");
$stmt->execute([$music_id]);

// Log play
$stmt = $pdo->prepare("INSERT INTO music_play_history (music_id, user_id, ip_address) VALUES (?, ?, ?)");
$stmt->execute([$music_id, $_SESSION['user_id'], $_SERVER['REMOTE_ADDR'] ?? 'unknown']);

echo json_encode(['success' => true]);
?>