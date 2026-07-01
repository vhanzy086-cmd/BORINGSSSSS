<?php
require_once '../config.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$pdo = getDB();

// Get stats
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
$total_users = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM keys_table");
$total_keys = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM banned_users");
$total_banned = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM music");
$total_music = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="neon-container">
        <header class="neon-header">
            <h1 class="neon-title">👑 Admin Dashboard</h1>
            <nav class="neon-nav">
                <a href="../index.php" class="neon-btn neon-btn-small"><i class="fas fa-home"></i> Home</a>
                <a href="../logout.php" class="neon-btn neon-btn-small"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </header>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_users; ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-key"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_keys; ?></h3>
                    <p>Total Keys</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-ban"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_banned; ?></h3>
                    <p>Banned Users</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-music"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total_music; ?></h3>
                    <p>Songs Uploaded</p>
                </div>
            </div>
        </div>

        <!-- Admin Menu -->
        <div class="neon-card">
            <div class="card-header">
                <h2><i class="fas fa-cog"></i> Admin Controls</h2>
                <div class="neon-border"></div>
            </div>
            <div class="card-body">
                <div class="admin-grid">
                    <a href="keys.php" class="admin-item">
                        <i class="fas fa-key"></i>
                        <span>Manage Keys</span>
                    </a>
                    <a href="users.php" class="admin-item">
                        <i class="fas fa-users-cog"></i>
                        <span>Manage Users</span>
                    </a>
                    <a href="banned.php" class="admin-item">
                        <i class="fas fa-ban"></i>
                        <span>Banned Users</span>
                    </a>
                    <a href="music.php" class="admin-item">
                        <i class="fas fa-music"></i>
                        <span>Manage Music</span>
                    </a>
                    <a href="logs.php" class="admin-item">
                        <i class="fas fa-history"></i>
                        <span>View Logs</span>
                    </a>
                    <a href="settings.php" class="admin-item">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <style>
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
        }
        .admin-item {
            background: rgba(0, 240, 255, 0.05);
            border: 1px solid var(--neon-primary);
            border-radius: 10px;
            padding: 25px 20px;
            text-align: center;
            text-decoration: none;
            color: var(--text-light);
            transition: all 0.3s ease;
        }
        .admin-item:hover {
            background: rgba(0, 240, 255, 0.1);
            transform: scale(1.05);
            box-shadow: var(--glow-primary);
        }
        .admin-item i {
            font-size: 2.5rem;
            color: var(--neon-primary);
            display: block;
            margin-bottom: 10px;
        }
        .admin-item span {
            font-size: 0.9rem;
            display: block;
        }
    </style>
</body>
</html>