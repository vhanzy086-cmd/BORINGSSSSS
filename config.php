<?php
// config.php
session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bot_generator');

define('SITE_URL', 'http://localhost/generator/');
define('SITE_NAME', 'Neon Generator');
define('UPLOAD_DIR', __DIR__ . '/uploads/feedback/');

// Create upload directory if not exists
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Connect to database
function getDB() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Get user info
function getUserInfo($user_id) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

// Get admin info
function getAdminInfo($admin_id) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM admin_accounts WHERE id = ?");
    $stmt->execute([$admin_id]);
    return $stmt->fetch();
}

// Check if user is banned
function isBanned($user_id) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM banned_users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch() !== false;
}

// Log action
function logAction($user_id, $action, $details = '', $ip = null) {
    if (!$ip) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $action, $details, $ip]);
}

// Send to Telegram
function sendToTelegram($message, $file_path = null) {
    // Get settings
    $pdo = getDB();
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    $bot_token = $settings['telegram_bot_token'] ?? '8224702445:AAFSwKnh7X_mRdN-CzGFpziI86vni6N1SD0';
    $admin_id = $settings['telegram_admin_id'] ?? '5318214551';
    
    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    
    $data = [
        'chat_id' => $admin_id,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    
    // Send message
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $result = curl_exec($ch);
    curl_close($ch);
    
    // If file exists, send file
    if ($file_path && file_exists($file_path)) {
        $file_url = "https://api.telegram.org/bot{$bot_token}/sendDocument";
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_path);
        finfo_close($finfo);
        
        $file_data = [
            'chat_id' => $admin_id,
            'document' => new CURLFile($file_path, $mime_type, basename($file_path)),
            'caption' => "📎 Attachment"
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $file_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $file_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $result = curl_exec($ch);
        curl_close($ch);
    }
    
    return true;
}

// Generate random key
function generateKey($length = 10) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $key = 'KEY-';
    for ($i = 0; $i < $length; $i++) {
        $key .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $key;
}

// Get feedback count for user
function getFeedbackCount($user_id) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM feedback WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch()['count'];
}
?>