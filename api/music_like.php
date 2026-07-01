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

// Update likes
$stmt = $pdo->prepare("UPDATE music SET likes = likes + 1 WHERE id = ?");
$stmt->execute([$music_id]);

echo json_encode(['success' => true]);
?>