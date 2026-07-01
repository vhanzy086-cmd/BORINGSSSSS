<?php
require_once 'config.php';

// Check if user is logged in
if (isLoggedIn()) {
    // Get user info
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    // Check if banned
    if (isBanned($_SESSION['user_id'])) {
        session_destroy();
        header('Location: login.php?error=banned');
        exit();
    }
    
<!-- Add to navigation -->
<a href="status.php" class="neon-btn neon-btn-small">
    <i class="fas fa-chart-line"></i> Status
</a>
<a href="spotikufal.php" class="neon-btn neon-btn-small">
    <i class="fas fa-music"></i> Music
</a>
    
    // Check if admin
    $is_admin = isAdmin();
} else {
    $user = null;
    $is_admin = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neon Generator</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="neon-container">
        <!-- Header -->
        <header class="neon-header">
            <div class="logo-container">
                <h1 class="neon-title">⚡ NEON GENERATOR</h1>
                <div class="neon-line"></div>
            </div>
            <nav class="neon-nav">
                <?php if (isLoggedIn()): ?>
                    <span class="user-badge">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['username'] ?? 'User'); ?>
                        <?php if ($is_admin): ?>
                            <span class="admin-badge">👑 Admin</span>
                        <?php endif; ?>
                    </span>
                    <a href="logout.php" class="neon-btn neon-btn-small">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="login.php" class="neon-btn neon-btn-small">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a href="register.php" class="neon-btn neon-btn-small">
                        <i class="fas fa-user-plus"></i> Register
                    </a>
                <?php endif; ?>
            </nav>
        </header>

        <!-- Main Content -->
        <main class="neon-main">
            <!-- Hero Section with Slider -->
            <section class="hero-section">
                <div class="slider-container">
                    <div class="slider">
                        <div class="slide active">
                            <div class="slide-content">
                                <h2>🚀 Premium Generator</h2>
                                <p>Generate accounts for all platforms</p>
                            </div>
                        </div>
                        <div class="slide">
                            <div class="slide-content">
                                <h2>🔐 Secure System</h2>
                                <p>Advanced security with admin panel</p>
                            </div>
                        </div>
                        <div class="slide">
                            <div class="slide-content">
                                <h2>🎵 Music Player</h2>
                                <p>Enjoy music while generating</p>
                            </div>
                        </div>
                    </div>
                    <div class="slider-nav">
                        <button class="slider-btn prev-btn"><i class="fas fa-chevron-left"></i></button>
                        <div class="slider-dots">
                            <span class="dot active" data-index="0"></span>
                            <span class="dot" data-index="1"></span>
                            <span class="dot" data-index="2"></span>
                        </div>
                        <button class="slider-btn next-btn"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
            </section>

            <!-- Generator Section -->
            <section class="generator-section">
                <div class="neon-card">
                    <div class="card-header">
                        <h2><i class="fas fa-cogs"></i> Generator</h2>
                        <div class="neon-border"></div>
                    </div>
                    <div class="card-body">
                        <?php if (isLoggedIn()): ?>
                            <div class="generator-form">
                                <div class="form-group">
                                    <label for="domain-select">Select Domain</label>
                                    <select id="domain-select" class="neon-select">
                                        <option value="100082">100082</option>
                                        <option value="authgop">AuthGop</option>
                                        <option value="mtacc">MTACC</option>
                                        <option value="garena">Garena</option>
                                        <option value="roblox">Roblox</option>
                                        <option value="mobilelegends">Mobile Legends</option>
                                        <option value="pubg">PUBG</option>
                                        <option value="facebook">Facebook</option>
                                        <option value="instagram">Instagram</option>
                                        <option value="netflix">Netflix</option>
                                        <option value="tiktok">TikTok</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="count-input">Count</label>
                                    <input type="number" id="count-input" class="neon-input" value="100" min="1" max="5000">
                                </div>
                                <button id="generate-btn" class="neon-btn neon-btn-large">
                                    <i class="fas fa-bolt"></i> Generate Now
                                </button>
                            </div>
                            <div id="generator-result" class="generator-result"></div>
                        <?php else: ?>
                            <div class="login-prompt">
                                <i class="fas fa-lock fa-3x"></i>
                                <p>Please <a href="login.php">login</a> or <a href="register.php">register</a> to use the generator</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- Stats Section -->
            <section class="stats-section">
                <div class="stats-grid" id="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-info">
                            <h3 id="total-users">0</h3>
                            <p>Total Users</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-key"></i></div>
                        <div class="stat-info">
                            <h3 id="total-keys">0</h3>
                            <p>Total Keys</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-info">
                            <h3 id="active-users">0</h3>
                            <p>Active Users</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-music"></i></div>
                        <div class="stat-info">
                            <h3 id="total-songs">0</h3>
                            <p>Songs Available</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Music Player -->
            <section class="music-section">
                <div class="neon-card music-player-card">
                    <div class="card-header">
                        <h2><i class="fas fa-music"></i> Music Player</h2>
                        <div class="neon-border"></div>
                    </div>
                    <div class="card-body">
                        <div class="music-player">
                            <div class="player-controls">
                                <button id="prev-song" class="player-btn"><i class="fas fa-step-backward"></i></button>
                                <button id="play-toggle" class="player-btn play-btn"><i class="fas fa-play"></i></button>
                                <button id="next-song" class="player-btn"><i class="fas fa-step-forward"></i></button>
                            </div>
                            <div class="player-info">
                                <div class="song-title" id="current-song">No song playing</div>
                                <div class="song-artist" id="current-artist">-</div>
                            </div>
                            <div class="progress-container">
                                <span class="time-current" id="current-time">0:00</span>
                                <input type="range" class="progress-bar" id="progress-bar" min="0" max="100" value="0">
                                <span class="time-total" id="total-time">0:00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Feedback Section -->
            <?php if (isLoggedIn()): ?>
            <section class="feedback-section">
                <div class="neon-card">
                    <div class="card-header">
                        <h2><i class="fas fa-comment"></i> Feedback</h2>
                        <div class="neon-border"></div>
                    </div>
                    <div class="card-body">
                        <form id="feedback-form" class="feedback-form">
                            <textarea id="feedback-message" class="neon-textarea" placeholder="Write your feedback here..." rows="3"></textarea>
                            <button type="submit" class="neon-btn neon-btn-small">
                                <i class="fas fa-paper-plane"></i> Send Feedback
                            </button>
                        </form>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <!-- Admin Panel -->
            <?php if ($is_admin): ?>
            <section class="admin-section">
                <div class="neon-card admin-card">
                    <div class="card-header">
                        <h2><i class="fas fa-shield-alt"></i> Admin Panel</h2>
                        <div class="neon-border"></div>
                    </div>
                    <div class="card-body">
                        <div class="admin-grid">
                            <div class="admin-item" onclick="location.href='admin/keys.php'">
                                <i class="fas fa-key"></i>
                                <span>Manage Keys</span>
                            </div>
                            <div class="admin-item" onclick="location.href='admin/users.php'">
                                <i class="fas fa-users-cog"></i>
                                <span>Manage Users</span>
                            </div>
                            <div class="admin-item" onclick="location.href='admin/banned.php'">
                                <i class="fas fa-ban"></i>
                                <span>Banned Users</span>
                            </div>
                            <div class="admin-item" onclick="location.href='admin/music.php'">
                                <i class="fas fa-music"></i>
                                <span>Manage Music</span>
                            </div>
                            <div class="admin-item" onclick="location.href='admin/logs.php'">
                                <i class="fas fa-history"></i>
                                <span>View Logs</span>
                            </div>
                            <div class="admin-item" onclick="location.href='admin/feedback.php'">
                                <i class="fas fa-comments"></i>
                                <span>Feedback</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <?php endif; ?>
        </main>

        <!-- Footer -->
        <footer class="neon-footer">
            <div class="footer-content">
                <p>&copy; 2024 Neon Generator. All rights reserved.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-telegram"></i></a>
                    <a href="#"><i class="fab fa-discord"></i></a>
                    <a href="#"><i class="fab fa-github"></i></a>
                </div>
            </div>
        </footer>
    </div>

    <script src="js/script.js"></script>
</body>
</html>