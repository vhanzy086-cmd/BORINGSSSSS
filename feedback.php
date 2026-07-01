<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_info = getUserInfo($user_id);
$feedback_count = getFeedbackCount($user_id);

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');
    $file_path = null;
    $file_type = null;
    
    if (empty($message)) {
        $error = 'Please enter a message';
    } else {
        // Handle file upload
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['attachment'];
            $allowed_types = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                'video/mp4', 'video/quicktime', 'video/webm',
                'audio/mpeg', 'audio/mp3', 'audio/wav',
                'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'text/plain'
            ];
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime_type, $allowed_types)) {
                $error = 'File type not allowed';
            } elseif ($file['size'] > 50 * 1024 * 1024) {
                $error = 'File too large (max 50MB)';
            } else {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = time() . '_' . uniqid() . '.' . $ext;
                $file_path = UPLOAD_DIR . $filename;
                
                if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                    $error = 'Failed to upload file';
                } else {
                    $file_type = explode('/', $mime_type)[0];
                }
            }
        }
        
        if (!isset($error)) {
            // Save to database
            $pdo = getDB();
            $stmt = $pdo->prepare("
                INSERT INTO feedback (user_id, username, message, file_path, file_type) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $user_info['username'],
                $message,
                $file_path,
                $file_type
            ]);
            
            $feedback_id = $pdo->lastInsertId();
            
            // Send to Telegram
            $telegram_message = "📨 <b>New Feedback Received</b>\n\n";
            $telegram_message .= "👤 User: <a href='tg://user?id={$user_id}'>{$user_info['username']}</a>\n";
            $telegram_message .= "🆔 ID: <code>{$user_id}</code>\n";
            $telegram_message .= "📝 Message:\n{$message}\n";
            $telegram_message .= "📎 File: " . ($file_path ? basename($file_path) : 'None');
            
            sendToTelegram($telegram_message, $file_path);
            
            // Log action
            logAction($user_id, 'feedback_sent', "Feedback ID: $feedback_id");
            
            $success = "Thank you for your feedback! We'll review it shortly.";
        }
    }
}

// Get user's feedback history
$pdo = getDB();
$stmt = $pdo->prepare("
    SELECT * FROM feedback 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 20
");
$stmt->execute([$user_id]);
$user_feedback = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - Neon Generator</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .feedback-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .feedback-form-container {
            margin-bottom: 30px;
        }
        .file-upload-area {
            border: 2px dashed var(--neon-primary);
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        .file-upload-area:hover {
            background: rgba(0, 240, 255, 0.05);
            border-color: var(--neon-secondary);
        }
        .file-upload-area.dragover {
            background: rgba(0, 240, 255, 0.1);
            border-color: var(--neon-secondary);
            box-shadow: var(--glow-primary);
        }
        .file-upload-area i {
            font-size: 3rem;
            color: var(--neon-primary);
            margin-bottom: 10px;
        }
        .file-upload-area p {
            color: var(--text-light);
            opacity: 0.7;
        }
        .file-info {
            display: none;
            background: rgba(0, 240, 255, 0.05);
            border: 1px solid var(--neon-primary);
            border-radius: 10px;
            padding: 10px 15px;
            margin-top: 10px;
            align-items: center;
            gap: 10px;
        }
        .file-info.show {
            display: flex;
        }
        .file-info i {
            color: var(--neon-primary);
        }
        .file-info .file-name {
            flex: 1;
            font-size: 0.9rem;
        }
        .file-info .remove-file {
            color: #ff4444;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .file-info .remove-file:hover {
            background: rgba(255, 0, 0, 0.1);
        }
        .feedback-history {
            margin-top: 30px;
        }
        .feedback-item {
            background: rgba(0, 240, 255, 0.03);
            border: 1px solid rgba(0, 240, 255, 0.1);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .feedback-item:hover {
            border-color: var(--neon-primary);
        }
        .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .feedback-status {
            padding: 2px 12px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: bold;
        }
        .status-pending {
            background: rgba(255, 170, 0, 0.2);
            color: #ffaa00;
        }
        .status-read {
            background: rgba(0, 240, 255, 0.2);
            color: var(--neon-primary);
        }
        .status-resolved {
            background: rgba(0, 255, 0, 0.2);
            color: #00ff00;
        }
        .status-replied {
            background: rgba(255, 0, 255, 0.2);
            color: var(--neon-secondary);
        }
        .feedback-message {
            margin: 10px 0;
            line-height: 1.6;
            color: var(--text-light);
        }
        .feedback-file {
            margin-top: 10px;
        }
        .feedback-file a {
            color: var(--neon-primary);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border: 1px solid var(--neon-primary);
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .feedback-file a:hover {
            background: var(--neon-primary);
            color: var(--bg-dark);
        }
        .feedback-reply {
            margin-top: 10px;
            padding: 10px 15px;
            background: rgba(255, 0, 255, 0.05);
            border-left: 3px solid var(--neon-secondary);
            border-radius: 5px;
        }
        .feedback-reply .reply-label {
            color: var(--neon-secondary);
            font-size: 0.8rem;
            font-weight: bold;
        }
        .feedback-reply .reply-text {
            margin-top: 5px;
            color: var(--text-light);
        }
        .feedback-reply .reply-time {
            font-size: 0.7rem;
            opacity: 0.6;
            margin-top: 5px;
        }
        .feedback-empty {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-light);
            opacity: 0.7;
        }
        .feedback-empty i {
            font-size: 3rem;
            color: var(--neon-primary);
            margin-bottom: 15px;
        }
        .character-count {
            text-align: right;
            font-size: 0.8rem;
            opacity: 0.6;
            margin-top: 5px;
        }
        .message-container {
            position: relative;
        }
    </style>
</head>
<body>
    <div class="neon-container">
        <header class="neon-header">
            <h1 class="neon-title">💬 Feedback</h1>
            <nav class="neon-nav">
                <a href="index.php" class="neon-btn neon-btn-small"><i class="fas fa-home"></i> Home</a>
                <a href="logout.php" class="neon-btn neon-btn-small"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </header>

        <div class="feedback-container">
            <!-- Feedback Form -->
            <div class="neon-card feedback-form-container">
                <div class="card-header">
                    <h2><i class="fas fa-pen"></i> Send Feedback</h2>
                    <div class="neon-border"></div>
                </div>
                <div class="card-body">
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data" id="feedback-form">
                        <div class="form-group">
                            <label for="feedback-message">Your Message <span class="required">*</span></label>
                            <div class="message-container">
                                <textarea id="feedback-message" name="message" class="neon-textarea" 
                                          rows="4" maxlength="2000" required
                                          placeholder="Describe your issue, suggestion, or feedback..."><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                                <div class="character-count"><span id="char-count">0</span>/2000</div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Attachment <span style="opacity:0.6;font-size:0.8rem;">(Optional - Max 50MB)</span></label>
                            <div class="file-upload-area" id="drop-zone">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Drag & drop files here or click to browse</p>
                                <p style="font-size:0.7rem;opacity:0.5;">Supported: Images, Videos, Audio, PDF, Word, Text</p>
                                <input type="file" name="attachment" id="file-input" style="display:none;" accept=".jpg,.jpeg,.png,.gif,.webp,.mp4,.mov,.webm,.mp3,.wav,.pdf,.doc,.docx,.txt">
                            </div>
                            <div class="file-info" id="file-info">
                                <i class="fas fa-file"></i>
                                <span class="file-name" id="file-name">No file selected</span>
                                <span class="remove-file" id="remove-file"><i class="fas fa-times"></i> Remove</span>
                            </div>
                        </div>
                        
                        <button type="submit" class="neon-btn neon-btn-large">
                            <i class="fas fa-paper-plane"></i> Submit Feedback
                        </button>
                    </form>
                </div>
            </div>

            <!-- Feedback History -->
            <div class="neon-card feedback-history">
                <div class="card-header">
                    <h2><i class="fas fa-history"></i> Your Feedback History</h2>
                    <div class="neon-border"></div>
                </div>
                <div class="card-body">
                    <?php if (empty($user_feedback)): ?>
                        <div class="feedback-empty">
                            <i class="fas fa-inbox"></i>
                            <p>You haven't sent any feedback yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($user_feedback as $feedback): ?>
                        <div class="feedback-item">
                            <div class="feedback-header">
                                <div>
                                    <span class="feedback-status status-<?php echo $feedback['status']; ?>">
                                        <?php echo ucfirst($feedback['status']); ?>
                                    </span>
                                </div>
                                <small style="opacity:0.6;">
                                    <i class="far fa-clock"></i> <?php echo date('M d, Y h:i A', strtotime($feedback['created_at'])); ?>
                                </small>
                            </div>
                            <div class="feedback-message">
                                <?php echo nl2br(htmlspecialchars($feedback['message'])); ?>
                            </div>
                            <?php if ($feedback['file_path']): ?>
                            <div class="feedback-file">
                                <a href="<?php echo htmlspecialchars($feedback['file_path']); ?>" target="_blank">
                                    <i class="fas fa-paperclip"></i> View Attachment
                                </a>
                            </div>
                            <?php endif; ?>
                            <?php if ($feedback['admin_reply']): ?>
                            <div class="feedback-reply">
                                <div class="reply-label">
                                    <i class="fas fa-reply"></i> Admin Response
                                </div>
                                <div class="reply-text">
                                    <?php echo nl2br(htmlspecialchars($feedback['admin_reply'])); ?>
                                </div>
                                <div class="reply-time">
                                    <?php echo date('M d, Y h:i A', strtotime($feedback['replied_at'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Character counter
        const messageInput = document.getElementById('feedback-message');
        const charCount = document.getElementById('char-count');
        
        messageInput.addEventListener('input', function() {
            const count = this.value.length;
            charCount.textContent = count;
            if (count > 1800) {
                charCount.style.color = '#ffaa00';
            } else if (count > 1900) {
                charCount.style.color = '#ff4444';
            } else {
                charCount.style.color = '';
            }
        });
        
        // Trigger initial count
        messageInput.dispatchEvent(new Event('input'));
        
        // File upload
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('file-input');
        const fileInfo = document.getElementById('file-info');
        const fileName = document.getElementById('file-name');
        const removeFile = document.getElementById('remove-file');
        let selectedFile = null;
        
        dropZone.addEventListener('click', function() {
            fileInput.click();
        });
        
        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        dropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        
        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                handleFile(e.dataTransfer.files[0]);
            }
        });
        
        fileInput.addEventListener('change', function() {
            if (this.files.length) {
                handleFile(this.files[0]);
            }
        });
        
        function handleFile(file) {
            // Check size (50MB)
            if (file.size > 50 * 1024 * 1024) {
                alert('File too large. Maximum size is 50MB.');
                return;
            }
            
            // Check type
            const allowedTypes = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                'video/mp4', 'video/quicktime', 'video/webm',
                'audio/mpeg', 'audio/mp3', 'audio/wav',
                'application/pdf', 'application/msword', 
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'text/plain'
            ];
            
            if (!allowedTypes.includes(file.type)) {
                alert('File type not supported. Please upload an image, video, audio, PDF, Word, or text file.');
                return;
            }
            
            selectedFile = file;
            fileName.textContent = file.name;
            fileInfo.classList.add('show');
            dropZone.style.display = 'none';
        }
        
        removeFile.addEventListener('click', function() {
            selectedFile = null;
            fileInput.value = '';
            fileInfo.classList.remove('show');
            dropZone.style.display = 'block';
        });
        
        // Form submission
        const form = document.getElementById('feedback-form');
        form.addEventListener('submit', function(e) {
            const message = messageInput.value.trim();
            if (!message) {
                e.preventDefault();
                alert('Please enter a message');
                return;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            submitBtn.disabled = true;
        });
    });
    </script>
</body>
</html>