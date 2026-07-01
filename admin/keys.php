<?php
require_once '../config.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$pdo = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generate_key'])) {
        $key_type = $_POST['key_type'] ?? '1d';
        $key_code = generateKey();
        
        $expiry = null;
        switch ($key_type) {
            case '1m': $expiry = date('Y-m-d H:i:s', strtotime('+1 minute')); break;
            case '5m': $expiry = date('Y-m-d H:i:s', strtotime('+5 minutes')); break;
            case '1h': $expiry = date('Y-m-d H:i:s', strtotime('+1 hour')); break;
            case '1d': $expiry = date('Y-m-d H:i:s', strtotime('+1 day')); break;
            case '3d': $expiry = date('Y-m-d H:i:s', strtotime('+3 days')); break;
            case '7d': $expiry = date('Y-m-d H:i:s', strtotime('+7 days')); break;
            case '15d': $expiry = date('Y-m-d H:i:s', strtotime('+15 days')); break;
            case '30d': $expiry = date('Y-m-d H:i:s', strtotime('+30 days')); break;
            case 'lifetime': $expiry = null; break;
        }
        
        $stmt = $pdo->prepare("INSERT INTO keys_table (key_code, key_type, expiry) VALUES (?, ?, ?)");
        $stmt->execute([$key_code, $key_type, $expiry]);
        
        logAction($_SESSION['user_id'], 'generate_key', "Generated key: $key_code");
        $message = "Key generated: $key_code";
    }
    
    if (isset($_POST['delete_key'])) {
        $key_id = (int)$_POST['key_id'];
        $stmt = $pdo->prepare("DELETE FROM keys_table WHERE id = ?");
        $stmt->execute([$key_id]);
        $message = "Key deleted";
    }
}

// Get keys
$stmt = $pdo->query("SELECT k.*, u.username as owner FROM keys_table k LEFT JOIN users u ON k.owner_id = u.id ORDER BY k.created_at DESC");
$keys = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Keys - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="neon-container">
        <header class="neon-header">
            <h1 class="neon-title">🔑 Manage Keys</h1>
            <nav class="neon-nav">
                <a href="../index.php" class="neon-btn neon-btn-small"><i class="fas fa-home"></i> Home</a>
                <a href="admin.php" class="neon-btn neon-btn-small"><i class="fas fa-shield-alt"></i> Admin</a>
                <a href="../logout.php" class="neon-btn neon-btn-small"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </header>

        <div class="neon-card">
            <div class="card-header">
                <h2><i class="fas fa-plus-circle"></i> Generate New Key</h2>
                <div class="neon-border"></div>
            </div>
            <div class="card-body">
                <?php if (isset($message)): ?>
                    <div class="admin-message"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <form method="POST" class="admin-form">
                    <div class="form-group">
                        <label for="key_type">Key Type</label>
                        <select id="key_type" name="key_type" class="neon-select">
                            <option value="1m">1 Minute</option>
                            <option value="5m">5 Minutes</option>
                            <option value="1h">1 Hour</option>
                            <option value="1d">1 Day</option>
                            <option value="3d">3 Days</option>
                            <option value="7d" selected>7 Days</option>
                            <option value="15d">15 Days</option>
                            <option value="30d">30 Days</option>
                            <option value="lifetime">Lifetime</option>
                        </select>
                    </div>
                    <button type="submit" name="generate_key" class="neon-btn">
                        <i class="fas fa-key"></i> Generate Key
                    </button>
                </form>
            </div>
        </div>

        <div class="neon-card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> All Keys</h2>
                <div class="neon-border"></div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Key</th>
                                <th>Type</th>
                                <th>Owner</th>
                                <th>Expiry</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($keys as $key): ?>
                            <tr>
                                <td><?php echo $key['id']; ?></td>
                                <td><code><?php echo htmlspecialchars($key['key_code']); ?></code></td>
                                <td><?php echo htmlspecialchars($key['key_type']); ?></td>
                                <td><?php echo $key['owner'] ? htmlspecialchars($key['owner']) : 'Unused'; ?></td>
                                <td><?php echo $key['expiry'] ? date('Y-m-d H:i', strtotime($key['expiry'])) : 'Lifetime'; ?></td>
                                <td>
                                    <?php
                                    if ($key['owner_id']) {
                                        echo '<span class="status-used">Used</span>';
                                    } elseif ($key['expiry'] && strtotime($key['expiry']) < time()) {
                                        echo '<span class="status-expired">Expired</span>';
                                    } else {
                                        echo '<span class="status-available">Available</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="key_id" value="<?php echo $key['id']; ?>">
                                        <button type="submit" name="delete_key" class="btn-danger" onclick="return confirm('Delete this key?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
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
        .admin-form {
            display: flex;
            gap: 20px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        .admin-table th,
        .admin-table td {
            padding: 10px 15px;
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
        .status-available {
            color: #00ff00;
        }
        .status-used {
            color: #ffaa00;
        }
        .status-expired {
            color: #ff0000;
        }
        .btn-danger {
            background: transparent;
            border: 1px solid #ff0000;
            color: #ff0000;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-danger:hover {
            background: #ff0000;
            color: #fff;
        }
        .table-responsive {
            overflow-x: auto;
        }
        @media (max-width: 768px) {
            .admin-form {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</body>
</html>