<?php
require_once '../config.php';

if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit();
}

$pdo = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reply'])) {
        $feedback_id = (int)$_POST['feedback_id'];
        $reply = trim($_POST['reply_message'] ?? '');
        
        if (!empty($reply)) {
            $stmt = $pdo->prepare("
                UPDATE feedback 
                SET admin_reply = ?, 
                    status = 'replied', 
                    replied_by = ?, 
                    replied_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$reply, $_SESSION['admin_id'], $feedback_id]);
            
            // Get feedback info to send notification
            $stmt = $pdo->prepare("SELECT * FROM feedback WHERE id = ?");
            $stmt->execute([$feedback_id]);
            $feedback = $stmt->fetch();
            
            if ($feedback) {
                // Send reply to user via Telegram
                $message = "📩 <b>Admin Replied to Your Feedback</b>\n\n";
                $message .= "👤 User: <a href='tg://user?id={$feedback['user_id']}'>{$feedback['username']}</a>\n";
                $message .= "📝 Your Message:\n{$feedback['message']}\n\n";
                $message .= "💬 <b>Admin Reply:</b>\n{$reply}";
                
                sendToTelegram($message);
                
                logAction($_SESSION['admin_id'], 'feedback_reply', "Replied to feedback ID: $feedback_id");
            }
            
            $success = "Reply sent successfully!";
        }
    }
    
    if (isset($_POST['resolve'])) {
        $feedback_id = (int)$_POST['feedback_id'];
        $stmt = $pdo->prepare("UPDATE feedback SET status = 'resolved' WHERE id = ?");
        $stmt->execute([$feedback_id]);
        $success = "Feedback marked as resolved";
        logAction($_SESSION['admin_id'], 'feedback_resolve', "Resolved feedback ID: $feedback_id");
    }
    
    if (isset($_POST['delete'])) {
        $feedback_id = (int)$_POST['feedback_id'];
        
        // Get file path
        $stmt = $pdo->prepare("SELECT file_path FROM feedback WHERE id = ?");
        $stmt->execute([$feedback_id]);
        $feedback = $stmt->fetch();
        
        if ($feedback && $feedback['file_path'] && file_exists($feedback['file_path'])) {
            unlink($feedback['file_path']);
        }
        
        $stmt = $pdo->prepare("DELETE FROM feedback WHERE id = ?");
        $stmt->execute([$feedback_id]);
        $success = "Feedback deleted";
        logAction($_SESSION['admin_id'], 'feedback_delete', "Deleted feedback ID: $feedback_id");
    }
}

// Get filters
$status = $_GET['status'] ?? 'all';
$has_file = isset($_GET['has_file']) ? true : false;

// Build query
$query = "
    SELECT f.*, u.username as user_name 
    FROM feedback f 
    LEFT JOIN users u ON f.user_id = u.id 
    WHERE 1=1
";
$params = [];

if ($status !== 'all' && in_array($status, ['pending', 'read', 'resolved', 'replied'])) {
    $query .= " AND f.status = ?";
    $params[] = $status;
}

if ($has_file) {
    $query .= " AND f.file_path IS NOT NULL";
}

$query .= " ORDER BY f.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$feedback_list = $stmt->fetchAll();

// Get single feedback for view
$single_feedback = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("
        SELECT f.*, u.username as user_name 
        FROM feedback f 
        LEFT JOIN users u ON f.user_id = u.id 
        WHERE f.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $single_feedback = $stmt->fetch();
}

// Get feedback for reply
$reply_feedback = null;
if (isset($_GET['reply'])) {
    $stmt = $pdo->prepare("SELECT * FROM feedback WHERE id = ?");
    $stmt->execute([$_GET['reply']]);
    $reply_feedback = $stmt->fetch();
}

// Handle resolve via GET
if (isset($_GET['resolve'])) {
    $feedback_id = (int)$_GET['resolve'];
    $stmt = $pdo->prepare("UPDATE feedback SET status = 'resolved' WHERE id = ?");
    $stmt->execute([$feedback_id]);
    header('Location: feedback.php?resolved=1');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Management - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .feedback-detail {
            max-width: 800px;
            margin: 0 auto;
        }
        .feedback-detail .detail-item {
            margin-bottom: 20px;
        }
        .feedback-detail .detail-label {
            font-size: 0.8rem;
            opacity: 0.6;
            margin-bottom: 5px;
        }
        .feedback-detail .detail-value {
            padding: 10px 15px;
            background: rgba(0, 240, 255, 0.03);
            border-radius: 10px;
            border: 1px solid rgba(0, 240, 255, 0.1);
        }
        .feedback-detail .detail-value .file-link {
            color: var(--neon-primary);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 15px;
            border: 1px solid var(--neon-primary);
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .feedback-detail .detail-value .file-link:hover {
            background: var(--neon-primary);
            color: var(--bg-dark);
        }
        .reply-form textarea {
            min-height: 120px;
        }
        .filter-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .filter-bar a {
            padding: 8px 15px;
            border-radius: 20px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.8rem;
        }
        .filter-bar a.active {
            background: var(--neon-primary);
            color: var(--bg-dark);
        }
        .filter-bar a:not(.active) {
            border: 1px solid var(--neon-primary);
            color: var(--neon-primary);
        }
        .filter-bar a:not(.active):hover {
            background: rgba(0, 240, 255, 0.1);
        }
        .feedback-item {
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding: 15px 0;
        }
        .feedback-item:last-child {
            border-bottom: none;
        }
        .feedback-item .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .feedback-item .feedback-body {
            margin: 10px 0;
        }
        .feedback-item .feedback-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .feedback-item .feedback-actions form {
            display: inline;
        }
        .btn-sm {
            padding: 4px 12px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 0.7rem;
            transition: all 0.3s ease;
        }
        .btn-sm:hover {
            transform: scale(1.05);
        }
        .btn-primary-sm {
            background: var(--neon-primary);
            color: var(--bg-dark);
        }
        .btn-secondary-sm {
            background: var(--neon-secondary);
            color: var(--bg-dark);
        }
        .btn-success-sm {
            background: #00ff00;
            color: var(--bg-dark);
        }
        .btn-danger-sm {
            background: #ff0000;
            color: #fff;
        }
        .btn-primary-sm a, .btn-secondary-sm a, .btn-success-sm a, .btn-danger-sm a {
            color: inherit;
            text-decoration: none;
        }
        .admin-message {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid #00ff00;
            border-radius: 10px;
            padding: 10px 15px;
            margin-bottom: 20px;
            color: #66ff66;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--neon-primary);
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .file-preview {
            max-width: 100%;
            max-height: 300px;
            border-radius: 10px;
            margin-top: 10px;
        }
        .file-preview video {
            max-width: 100%;
            max-height: 300px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="neon-container">
        <header class="neon-header">
            <h1 class="neon-title">💬 Feedback Management</h1>
            <nav class="neon-nav">
                <a href="dashboard.php" class="neon-btn neon-btn-small"><i class="fas fa-shield-alt"></i> Dashboard</a>
                <a href="../logout.php" class="neon-btn neon-btn-small"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </header>

        <?php if (isset($success)): ?>
            <div class="admin-message"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['resolved'])): ?>
            <div class="admin-message"><i class="fas fa-check-circle"></i> Feedback marked as resolved</div>
        <?php endif; ?>

        <?php if ($single_feedback): ?>
            <!-- Single Feedback View -->
            <a href="feedback.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to all feedback</a>
            
            <div class="neon-card feedback-detail">
                <div class="card-header">
                    <h2>Feedback #<?php echo $single_feedback['id']; ?></h2>
                    <div class="neon-border"></div>
                </div>
                <div class="card-body">
                    <div class="detail-item">
                        <div class="detail-label">User</div>
                        <div class="detail-value">
                            <?php echo htmlspecialchars($single_feedback['user_name'] ?? 'Unknown User'); ?>
                            <span style="font-size:0.8rem;opacity:0.6;">#<?php echo $single_feedback['user_id']; ?></span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Status</div>
                        <div class="detail-value">
                            <span class="status status-<?php echo $single_feedback['status']; ?>">
                                <?php echo ucfirst($single_feedback['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Date</div>
                        <div class="detail-value">
                            <?php echo date('F d, Y h:i:s A', strtotime($single_feedback['created_at'])); ?>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Message</div>
                        <div class="detail-value">
                            <?php echo nl2br(htmlspecialchars($single_feedback['message'])); ?>
                        </div>
                    </div>
                    <?php if ($single_feedback['file_path']): ?>
                    <div class="detail-item">
                        <div class="detail-label">Attachment</div>
                        <div class="detail-value">
                            <?php
                            $file_path = $single_feedback['file_path'];
                            $file_type = $single_feedback['file_type'] ?? '';
                            $is_image = in_array($file_type, ['image']);
                            $is_video = in_array($file_type, ['video']);
                            ?>
                            <?php if ($is_image): ?>
                                <img src="<?php echo htmlspecialchars($file_path); ?>" class="file-preview" alt="Feedback attachment">
                            <?php elseif ($is_video): ?>
                                <video controls class="file-preview">
                                    <source src="<?php echo htmlspecialchars($file_path); ?>">
                                </video>
                            <?php else: ?>
                                <a href="<?php echo htmlspecialchars($file_path); ?>" target="_blank" class="file-link">
                                    <i class="fas fa-download"></i> Download Attachment
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($single_feedback['admin_reply']): ?>
                    <div class="detail-item">
                        <div class="detail-label">Admin Reply</div>
                        <div class="detail-value" style="border-left: 3px solid var(--neon-secondary);">
                            <?php echo nl2br(htmlspecialchars($single_feedback['admin_reply'])); ?>
                            <div style="font-size:0.7rem;opacity:0.6;margin-top:5px;">
                                <?php echo date('M d, Y h:i A', strtotime($single_feedback['replied_at'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="detail-item">
                        <div class="detail-label">Actions</div>
                        <div class="detail-value">
                            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                                <?php if ($single_feedback['status'] !== 'replied' && $single_feedback['status'] !== 'resolved'): ?>
                                <a href="?reply=<?php echo $single_feedback['id']; ?>" class="neon-btn neon-btn-small">
                                    <i class="fas fa-reply"></i> Reply
                                </a>
                                <?php endif; ?>
                                <?php if ($single_feedback['status'] !== 'resolved'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="feedback_id" value="<?php echo $single_feedback['id']; ?>">
                                    <button type="submit" name="resolve" class="neon-btn neon-btn-small" style="border-color:#00ff00;color:#00ff00;">
                                        <i class="fas fa-check"></i> Resolve
                                    </button>
                                </form>
                                <?php endif; ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this feedback?')">
                                    <input type="hidden" name="feedback_id" value="<?php echo $single_feedback['id']; ?>">
                                    <button type="submit" name="delete" class="neon-btn neon-btn-small" style="border-color:#ff0000;color:#ff0000;">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php elseif ($reply_feedback): ?>
            <!-- Reply Form -->
            <a href="feedback.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to all feedback</a>
            
            <div class="neon-card">
                <div class="card-header">
                    <h2><i class="fas fa-reply"></i> Reply to Feedback #<?php echo $reply_feedback['id']; ?></h2>
                    <div class="neon-border"></div>
                </div>
                <div class="card-body">
                    <div style="margin-bottom:20px;padding:15px;background:rgba(0,240,255,0.03);border-radius:10px;">
                        <strong>Original Message:</strong>
                        <p style="margin-top:5px;"><?php echo nl2br(htmlspecialchars($reply_feedback['message'])); ?></p>
                        <small style="opacity:0.6;">
                            From: <?php echo htmlspecialchars($reply_feedback['username'] ?? 'Unknown User'); ?>
                            <span style="margin-left:10px;"><?php echo date('M d, Y h:i A', strtotime($reply_feedback['created_at'])); ?></span>
                        </small>
                    </div>
                    
                    <form method="POST" class="reply-form">
                        <input type="hidden" name="feedback_id" value="<?php echo $reply_feedback['id']; ?>">
                        <div class="form-group">
                            <label for="reply_message">Your Reply</label>
                            <textarea id="reply_message" name="reply_message" class="neon-textarea" rows="5" required></textarea>
                        </div>
                        <div style="display:flex;gap:10px;flex-wrap:wrap;">
                            <button type="submit" name="reply" class="neon-btn">
                                <i class="fas fa-paper-plane"></i> Send Reply
                            </button>
                            <a href="feedback.php" class="neon-btn neon-btn-small" style="border-color:#ff4444;color:#ff4444;">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Feedback List -->
            <div class="neon-card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> All Feedback</h2>
                    <div class="neon-border"></div>
                </div>
                <div class="card-body">
                    <div class="filter-bar">
                        <a href="?status=all" class="<?php echo $status === 'all' ? 'active' : ''; ?>">All</a>
                        <a href="?status=pending" class="<?php echo $status === 'pending' ? 'active' : ''; ?>">Pending</a>
                        <a href="?status=read" class="<?php echo $status === 'read' ? 'active' : ''; ?>">Read</a>
                        <a href="?status=replied" class="<?php echo $status === 'replied' ? 'active' : ''; ?>">Replied</a>
                        <a href="?status=resolved" class="<?php echo $status === 'resolved' ? 'active' : ''; ?>">Resolved</a>
                        <a href="?has_file=1" class="<?php echo $has_file ? 'active' : ''; ?>">With Files</a>
                    </div>
                    
                    <?php if (empty($feedback_list)): ?>
                        <div class="feedback-empty">
                            <i class="fas fa-inbox fa-3x"></i>
                            <p>No feedback found</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($feedback_list as $feedback): ?>
                        <div class="feedback-item">
                            <div class="feedback-header">
                                <div>
                                    <strong><?php echo htmlspecialchars($feedback['user_name'] ?? 'Unknown User'); ?></strong>
                                    <span style="font-size:0.7rem;opacity:0.6;">#<?php echo $feedback['user_id']; ?></span>
                                    <span class="status status-<?php echo $feedback['status']; ?>" style="margin-left:10px;">
                                        <?php echo ucfirst($feedback['status']); ?>
                                    </span>
                                </div>
                                <small style="opacity:0.6;">
                                    <?php echo date('M d, Y h:i A', strtotime($feedback['created_at'])); ?>
                                </small>
                            </div>
                            <div class="feedback-body">
                                <?php echo htmlspecialchars(substr($feedback['message'], 0, 200)) . (strlen($feedback['message']) > 200 ? '...' : ''); ?>
                                <?php if ($feedback['file_path']): ?>
                                <span style="margin-left:10px;color:var(--neon-primary);font-size:0.7rem;">
                                    <i class="fas fa-paperclip"></i>
                                </span>
                                <?php endif; ?>
                                <?php if ($feedback['admin_reply']): ?>
                                <span style="margin-left:10px;color:var(--neon-secondary);font-size:0.7rem;">
                                    <i class="fas fa-reply"></i> Replied
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="feedback-actions">
                                <a href="?id=<?php echo $feedback['id']; ?>" class="btn-sm btn-primary-sm">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <?php if ($feedback['status'] !== 'replied' && $feedback['status'] !== 'resolved'): ?>
                                <a href="?reply=<?php echo $feedback['id']; ?>" class="btn-sm btn-secondary-sm">
                                    <i class="fas fa-reply"></i> Reply
                                </a>
                                <?php endif; ?>
                                <?php if ($feedback['status'] !== 'resolved'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="feedback_id" value="<?php echo $feedback['id']; ?>">
                                    <button type="submit" name="resolve" class="btn-sm btn-success-sm">
                                        <i class="fas fa-check"></i> Resolve
                                    </button>
                                </form>
                                <?php endif; ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this feedback?')">
                                    <input type="hidden" name="feedback_id" value="<?php echo $feedback['id']; ?>">
                                    <button type="submit" name="delete" class="btn-sm btn-danger-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>