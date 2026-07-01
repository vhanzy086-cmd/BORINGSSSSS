<?php
header('Content-Type: application/json');
require_once '../config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$domain = $data['domain'] ?? '';
$count = (int)($data['count'] ?? 100);

if (empty($domain) || $count < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

$pdo = getDB();

// Check if user has valid key
$stmt = $pdo->prepare("SELECT * FROM keys_table WHERE owner_id = ? AND (expiry IS NULL OR expiry > NOW())");
$stmt->execute([$_SESSION['user_id']]);
$user_key = $stmt->fetch();

if (!$user_key) {
    echo json_encode(['success' => false, 'message' => 'No active key found. Please redeem a key first.']);
    exit();
}

// Check rate limit (max 5000 per request)
if ($count > 5000) {
    $count = 5000;
}

// Generate accounts (simulated)
$generated = [];
$remaining = 0;

// Check if domain files exist
$data_folder = '../data/';
$domain_file = $data_folder . $domain . '.txt';

if (file_exists($domain_file)) {
    $lines = file($domain_file, FILE_IGNORE_NEW_LINES);
    $remaining = count($lines);
    
    // Get used accounts
    $used_file = '../used_accounts.txt';
    $used = [];
    if (file_exists($used_file)) {
        $used = file($used_file, FILE_IGNORE_NEW_LINES);
    }
    
    // Filter unused accounts
    $available = array_diff($lines, $used);
    $available = array_values($available);
    $remaining = count($available);
    
    // Take requested count
    $take = min($count, $remaining);
    $generated = array_slice($available, 0, $take);
    
    // Mark as used
    if (!empty($generated)) {
        file_put_contents($used_file, implode("\n", $generated) . "\n", FILE_APPEND);
    }
} else {
    // Generate random accounts if no file exists
    for ($i = 0; $i < min($count, 100); $i++) {
        $username = 'user' . rand(100000, 999999);
        $password = 'pass' . rand(1000, 9999);
        $generated[] = $username . ':' . $password;
    }
}

logAction($_SESSION['user_id'], 'generate', "Generated $domain accounts: " . count($generated));

echo json_encode([
    'success' => true,
    'domain' => $domain,
    'count' => count($generated),
    'remaining' => $remaining,
    'download' => count($generated) > 0 ? 'api/download.php?file=generated_' . time() . '.txt' : null
]);
?>