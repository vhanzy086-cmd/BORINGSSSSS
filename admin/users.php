<?php
require_once '../config.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$pdo = getDB();

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ban_user'])) {
        $user_id = (int)$_POST['user_id'];
        $reason = $_POST['reason'] ?? 'No reason provided';
        
        $stmt = $pdo->prepare("INSERT INTO banned_users (user_id, reason, banned_by) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $reason, $_SESSION['user_id']]);
        
        logAction($_SESSION['user_id'], 'ban_user', "Banned user ID: $user_id");
        $message = "User banned successfully";
    }
    
    if (isset($_POST['unban_user'])) {
        $user_id = (int)$_POST['user_id'];
        
        $stmt = $pdo->prepare("DELETE FROM banned_users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        logAction($_SESSION['user_id'], 'unban_user', "Unbanned user ID: $user_id");
        $message = "User unbanned successfully";
    }
}

// Get users
$stmt = $pdo->query("
    SELECT u.*, 
           b.id as banned_id, b.reason as ban_reason,
           (SELECT COUNT(*) FROM keys_table WHERE owner_id = u.id) as key_count
    FROM users u
    LEFT JOIN banned_users b ON u.id = b.user_id
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="neon-container">
        <header class="neon-header">
            <h1 class="neon-title">👥 Manage Users</h1>
            <nav class="neon-nav">
                <a href="../index.php" class="neon-btn neon-btn-small"><i class="fas fa-home"></i> Home</a>
                <a href="admin.php" class="neon-btn neon-btn-small"><i class="fas fa-shield-alt"></i> Admin</a>
                <a href="../logout.php" class="neon-btn neon-btn-small"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </header>

        <div class="neon-card">
            <div class="card-header">
                <h2><i class="fas fa-users"></i> All Users</h2>
                <div class="neon-border"></div>
            </div>
            <div class="card-body">
                <?php if (isset($message)): ?>
                    <div class="admin-message"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Keys</th>
                                <th>Joined</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                <td><?php echo $user['key_count']; ?></td>
                                <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if ($user['banned_id']): ?>
                                        <span class="status-banned">Banned</span>
                                    <?php else: ?>
                                        <span class="status-active">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['banned_id']): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="unban_user" class="btn-success">
                                                <i class="fas fa-check"></i> Unban
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="text" name="reason" placeholder="Reason" class="small-input" required>
                                            <button type="submit" name="ban_user" class="btn-danger">
                                                <i class="fas fa-ban"></i> Ban
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <style>
        .admin-message {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid #00ff00;
            border-radius: 10px;
            padding: 10px 15px;
            margin-bottom: 20px;
            color: #66ff66;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
        }
        .admin-table th,
        .admin-table td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .admin-table th {
            color: var(--neon-primary);
            border-bottom: 2px solid var(--neon-primary);
        }
        .admin-table tr:hover {
            background: rgba(0, 240, 255, 0.05);
        }
        .status-active {
            color: #00ff00;
        }
        .status-banned {
            color: #ff0000;
        }
        .btn-danger, .btn-success {
            background: transparent;
            border: 1px solid #ff0000;
            color: #ff0000;
            padding: 3px 8px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.7rem;
        }
        .btn-success {
            border-color: #00ff00;
            color: #00ff00;
        }
        .btn-success:hover {
            background: #00ff00;
            color: #000;
        }
        .btn-danger:hover {
            background: #ff0000;
            color: #fff;
        }
        .small-input {
            padding: 3px 8px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            color: #fff;
            width: 100px;
            font-size: 0.7rem;
        }
        .table-responsive {
            overflow-x: auto;
        }
    </style>
</body>
</html>