<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    
    // Validate
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all required fields';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        $pdo = getDB();
        
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'Username already taken';
        } else {
            // Check if email exists
            if (!empty($email)) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'Email already registered';
                }
            }
            
            if (empty($error)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'user')");
                $stmt->execute([$username, $hashed_password, $email]);
                
                $user_id = $pdo->lastInsertId();
                logAction($user_id, 'register', 'User registered');
                
                $success = 'Registration successful! You can now login.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Neon Generator</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="neon-container">
        <div class="auth-container">
            <div class="neon-card auth-card">
                <div class="card-header">
                    <h2><i class="fas fa-user-plus"></i> Register</h2>
                    <div class="neon-border"></div>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="auth-error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="auth-success">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" class="auth-form">
                        <div class="form-group">
                            <label for="username">Username *</label>
                            <input type="text" id="username" name="username" class="neon-input" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email (optional)</label>
                            <input type="email" id="email" name="email" class="neon-input">
                        </div>
                        <div class="form-group">
                            <label for="password">Password *</label>
                            <input type="password" id="password" name="password" class="neon-input" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="neon-input" required>
                        </div>
                        <button type="submit" class="neon-btn neon-btn-large">
                            <i class="fas fa-user-plus"></i> Register
                        </button>
                    </form>
                    <div class="auth-links">
                        <p>Already have an account? <a href="login.php">Login</a></p>
                        <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <style>
        .auth-container {
            max-width: 400px;
            margin: 50px auto;
        }
        .auth-error {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid #ff0000;
            border-radius: 10px;
            padding: 10px 15px;
            margin-bottom: 20px;
            color: #ff6666;
        }
        .auth-success {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid #00ff00;
            border-radius: 10px;
            padding: 10px 15px;
            margin-bottom: 20px;
            color: #66ff66;
        }
        .auth-form .form-group {
            margin-bottom: 20px;
        }
        .auth-links {
            margin-top: 20px;
            text-align: center;
        }
        .auth-links a {
            color: var(--neon-primary);
            text-decoration: none;
        }
        .auth-links a:hover {
            text-decoration: underline;
        }
        .auth-links p {
            margin-bottom: 10px;
        }
    </style>
</body>
</html>