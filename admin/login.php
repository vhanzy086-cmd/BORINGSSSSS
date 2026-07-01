<?php
require_once '../config.php';

// If already logged in, redirect to admin dashboard
if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM admin_accounts WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_role'] = $admin['role'];
            
            // Update last login
            $stmt = $pdo->prepare("UPDATE admin_accounts SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$admin['id']]);
            
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Neon Generator</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="neon-container">
        <div class="auth-container">
            <div class="neon-card auth-card">
                <div class="card-header">
                    <h2><i class="fas fa-shield-alt"></i> Admin Login</h2>
                    <div class="neon-border"></div>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="auth-error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" class="auth-form">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" class="neon-input" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" class="neon-input" required>
                        </div>
                        <button type="submit" class="neon-btn neon-btn-large">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                    </form>
                    <div class="auth-links">
                        <a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
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
    </style>
</body>
</html>