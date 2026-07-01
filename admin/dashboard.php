<?php
require_once '../config.php';

if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit();
}

$admin_info = getAdminInfo($_SESSION['admin_id']);
$pdo = getDB();

// Get stats
$stmt = $pdo->query("SELECT COUNT(*) as count FROM feedback");
$total_feedback = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM feedback WHERE status = 'pending'");
$pending_feedback = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM feedback WHERE status = 'read'");
$read_feedback = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM feedback WHERE status = 'resolved'");
$resolved_feedback = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM feedback WHERE status = 'replied'");
$replied_feedback = $stmt->fetch()['count'];

// Get recent feedback
$stmt = $pdo->query("
    SELECT f.*, u.username as user_name 
    FROM feedback f 
    LEFT JOIN users u ON f.user_id = u.id 
    ORDER BY f.created_at DESC 
    LIMIT 10
");
$recent_feedback = $stmt->fetchAll();

<!-- Add music stats to dashboard -->
<?php
// Get music stats
$stmt = $pdo->query("SELECT COUNT(*) as count FROM music");
$total_music = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT SUM(plays) as total FROM music");
$total_plays = $stmt->fetch()['total'] ?? 0;
?>

<!-- Add to stats grid -->
<div class="admin-stat-card">
    <div class="stat-icon"><i class="fas fa-music"></i></div>
    <div class="stat-number"><?php echo $total_music; ?></div>
    <div class="stat-label">Total Songs</div>
</div>
<div class="admin-stat-card">
    <div class="stat-icon"><i class="fas fa-headphones"></i></div>
    <div class="stat-number"><?php echo number_format($total_plays); ?></div>
    <div class="stat-label">Total Plays</div>
</div>

// Get feedback with attachments
$stmt = $pdo->query("SELECT COUNT(*) as count FROM feedback WHERE file_path IS NOT NULL");
$feedback_with_files = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Neon Generator</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .admin-user {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 15px;
            border: 1px solid var(--neon-primary);
            border-radius: 20px;
            background: rgba(0, 240, 255, 0.05);
        }
        .admin-user .role-badge {
            padding: 2px 10px;
            border-radius: 10px;
            font-size: 0.7rem;
            background: var(--neon-secondary);
            color: #fff;
        }
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .admin-stat-card {
            background: var(--bg-card);
            border: 1px solid var(--neon-primary);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }
        .admin-stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--glow-primary);
        }
        .admin-stat-card .stat-number {
            font-size: 2.5rem;
            font-weight: 900;
            color: var(--neon-primary);
        }
        .admin-stat-card .stat-label {
            font-size: 0.9rem;
            opacity: 0.7;
            margin-top: 5px;
        }
        .admin-stat-card .stat-icon {
            font-size: 2rem;
            color: var(--neon-secondary);
            margin-bottom: 10px;
        }
        .admin-stat-card.pending .stat-number { color: #ffaa00; }
        .admin-stat-card.resolved .stat-number { color: #00ff00; }
        .admin-stat-card.replied .stat-number { color: var(--neon-secondary); }
        .admin-stat-card.files .stat-number { color: #00ccff; }
        
        .feedback-list-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
            gap: 15px;
            flex-wrap: wrap;
        }
        .feedback-list-item:hover {
            background: rgba(0, 240, 255, 0.03);
        }
        .feedback-list-item .feedback-info {
            flex: 1;
            min-width: 200px;
        }
        .feedback-list-item .feedback-info .user {
            font-weight: bold;
            color: var(--neon-primary);
        }
        .feedback-list-item .feedback-info .message {
            margin-top: 5px;
            opacity: 0.8;
        }
        .feedback-list-item .feedback-meta {
            text-align: right;
            min-width: 150px;
        }
        .feedback-list-item .feedback-meta .status {
            display: inline-block;
            padding: 2px 12px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: bold;
        }
        .feedback-list-item .feedback-meta .time {
            font-size: 0.7rem;
            opacity: 0.6;
            display: block;
            margin-top: 5px;
        }
        .feedback-list-item .feedback-actions {
            display: flex;
            gap: 5px;
            margin-top: 10px;
        }
        .feedback-list-item .feedback-actions a {
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.7rem;
            transition: all 0.3s ease;
        }
        .btn-view {
            background: rgba(0, 240, 255, 0.1);
            color: var(--neon-primary);
            border: 1px solid var(--neon-primary);
        }
        .btn-view:hover {
            background: var(--neon-primary);
            color: var(--bg-dark);
        }
        .btn-reply {
            background: rgba(255, 0, 255, 0.1);
            color: var(--neon-secondary);
            border: 1px solid var(--neon-secondary);
        }
        .btn-reply:hover {
            background: var(--neon-secondary);
            color: var(--bg-dark);
        }
        .btn-resolve {
            background: rgba(0, 255, 0, 0.1);
            color: #00ff00;
            border: 1px solid #00ff00;
        }
        .btn-resolve:hover {
            background: #00ff00;
            color: var(--bg-dark);
        }
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .quick-action {
            background: rgba(0, 240, 255, 0.05);
            border: 1px solid var(--neon-primary);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            text-decoration: none;
            color: var(--text-light);
            transition: all 0.3s ease;
        }
        .quick-action:hover {
            background: rgba(0, 240, 255, 0.1);
            transform: scale(1.05);
            box-shadow: var(--glow-primary);
        }
        .quick-action i {
            font-size: 2rem;
            color: var(--neon-primary);
            display: block;
            margin-bottom: 10px;
        }
        .quick-action span {
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="neon-container">
        <header class="neon-header">
            <h1 class="neon-title">👑 Admin Dashboard</h1>
            <div class="admin-user">
                <i class="fas fa-user"></i>
                <span><?php echo htmlspecialchars($admin_info['username']); ?></span>
                <span class="role-badge"><?php echo $admin_info['role']; ?></span>
                <a href="../logout.php" class="neon-btn neon-btn-small" style="margin-left:10px;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </header>

        <!-- Stats -->
        <div class="admin-grid">
            <div class="admin-stat-card pending">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-number"><?php echo $pending_feedback; ?></div>
                <div class="stat-label">Pending Feedback</div>
            </div>
            <div class="admin-stat-card">
                <div class="stat-icon"><i class="fas fa-envelope-open"></i></div>
                <div class="stat-number"><?php echo $read_feedback; ?></div>
                <div class="stat-label">Read</div>
            </div>
            <div class="admin-stat-card replied">
                <div class="stat-icon"><i class="fas fa-reply"></i></div>
                <div class="stat-number"><?php echo $replied_feedback; ?></div>
                <div class="stat-label">Replied</div>
            </div>
            <div class="admin-stat-card resolved">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-number"><?php echo $resolved_feedback; ?></div>
                <div class="stat-label">Resolved</div>
            </div>
            <div class="admin-stat-card files">
                <div class="stat-icon"><i class="fas fa-paperclip"></i></div>
                <div class="stat-number"><?php echo $feedback_with_files; ?></div>
                <div class="stat-label">With Attachments</div>
            </div>
            <div class="admin-stat-card">
                <div class="stat-icon"><i class="fas fa-inbox"></i></div>
                <div class="stat-number"><?php echo $total_feedback; ?></div>
                <div class="stat-label">Total Feedback</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="feedback.php?status=pending" class="quick-action">
                <i class="fas fa-clock"></i>
                <span>View Pending</span>
            </a>
            <a href="feedback.php?status=replied" class="quick-action">
                <i class="fas fa-reply-all"></i>
                <span>View Replied</span>
            </a>
            <a href="feedback.php?status=resolved" class="quick-action">
                <i class="fas fa-check-double"></i>
                <span>View Resolved</span>
            </a>
            <a href="feedback.php?has_file=1" class="quick-action">
                <i class="fas fa-paperclip"></i>
                <span>With Files</span>
            </a>
            <a href="keys.php" class="quick-action">
                <i class="fas fa-key"></i>
                <span>Manage Keys</span>
            </a>
            <a href="users.php" class="quick-action">
                <i class="fas fa-users-cog"></i>
                <span>Manage Users</span>
            </a>
        </div>

        <!-- Recent Feedback -->
        <div class="neon-card">
            <div class="card-header">
                <h2><i class="fas fa-history"></i> Recent Feedback</h2>
                <div class="neon-border"></div>
            </div>
            <div class="card-body">
                <?php if (empty($recent_feedback)): ?>
                    <div class="feedback-empty">
                        <i class="fas fa-inbox fa-3x"></i>
                        <p>No feedback yet</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_feedback as $feedback): ?>
                    <div class="feedback-list-item">
                        <div class="feedback-info">
                            <div class="user">
                                <i class="fas fa-user"></i> 
                                <?php echo htmlspecialchars($feedback['user_name'] ?? 'Unknown User'); ?>
                                <span style="font-size:0.7rem;opacity:0.6;">#<?php echo $feedback['user_id']; ?></span>
                            </div>
                            <div class="message">
                                <?php echo htmlspecialchars(substr($feedback['message'], 0, 150)) . (strlen($feedback['message']) > 150 ? '...' : ''); ?>
                            </div>
                            <?php if ($feedback['file_path']): ?>
                            <div style="margin-top:5px;font-size:0.7rem;color:var(--neon-primary);">
                                <i class="fas fa-paperclip"></i> Has attachment
                            </div>
                            <?php endif; ?>
                            <?php if ($feedback['admin_reply']): ?>
                            <div style="margin-top:5px;font-size:0.7rem;color:var(--neon-secondary);">
                                <i class="fas fa-reply"></i> Replied
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="feedback-meta">
                            <span class="status status-<?php echo $feedback['status']; ?>">
                                <?php echo ucfirst($feedback['status']); ?>
                            </span>
                            <span class="time">
                                <?php echo date('M d, Y h:i A', strtotime($feedback['created_at'])); ?>
                            </span>
                            <div class="feedback-actions">
                                <a href="feedback.php?id=<?php echo $feedback['id']; ?>" class="btn-view">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <?php if ($feedback['status'] !== 'replied' && $feedback['status'] !== 'resolved'): ?>
                                <a href="feedback.php?reply=<?php echo $feedback['id']; ?>" class="btn-reply">
                                    <i class="fas fa-reply"></i> Reply
                                </a>
                                <?php endif; ?>
                                <?php if ($feedback['status'] !== 'resolved'): ?>
                                <a href="feedback.php?resolve=<?php echo $feedback['id']; ?>" class="btn-resolve" onclick="return confirm('Mark this feedback as resolved?')">
                                    <i class="fas fa-check"></i> Resolve
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div style="text-align:center;margin-top:20px;">
                        <a href="feedback.php" class="neon-btn neon-btn-small">
                            <i class="fas fa-list"></i> View All Feedback
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>