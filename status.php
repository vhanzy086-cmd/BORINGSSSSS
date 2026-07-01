<?php
require_once 'config.php';

$pdo = getDB();

// Get website status
$stmt = $pdo->query("SELECT * FROM website_status ORDER BY id DESC LIMIT 1");
$status_data = $stmt->fetch() ?: ['status' => 'online', 'message' => 'All systems operational'];

// Get uptime stats (last 30 days)
$stmt = $pdo->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as total_requests,
        SUM(CASE WHEN is_blocked = 1 THEN 1 ELSE 0 END) as blocked_requests
    FROM ddos_protection_logs 
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
");
$uptime_data = $stmt->fetchAll();

// Get active users online (last 5 minutes)
$stmt = $pdo->query("
    SELECT COUNT(DISTINCT user_id) as active_users 
    FROM logs 
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
");
$active_users = $stmt->fetch()['active_users'] ?? 0;

// Get total requests
$stmt = $pdo->query("SELECT COUNT(*) as total FROM ddos_protection_logs");
$total_requests = $stmt->fetch()['total'] ?? 0;

// Get blocked requests
$stmt = $pdo->query("SELECT COUNT(*) as total FROM ddos_protection_logs WHERE is_blocked = 1");
$blocked_requests = $stmt->fetch()['total'] ?? 0;

// Calculate uptime percentage
$total_days = 30;
$available_days = $total_days - (count($uptime_data) > 0 ? 0 : 0);
$uptime_percentage = $total_days > 0 ? round(($available_days / $total_days) * 100, 2) : 100;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status - Neon Generator</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .status-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .status-header {
            text-align: center;
            padding: 40px 20px;
            margin-bottom: 30px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 10px 30px;
            border-radius: 30px;
            font-size: 1.2rem;
            font-weight: bold;
            animation: statusPulse 2s infinite;
        }
        
        @keyframes statusPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        
        .status-online {
            background: rgba(0, 255, 0, 0.2);
            border: 2px solid #00ff00;
            color: #00ff00;
            box-shadow: 0 0 30px rgba(0, 255, 0, 0.3);
        }
        
        .status-maintenance {
            background: rgba(255, 170, 0, 0.2);
            border: 2px solid #ffaa00;
            color: #ffaa00;
            box-shadow: 0 0 30px rgba(255, 170, 0, 0.3);
        }
        
        .status-offline {
            background: rgba(255, 0, 0, 0.2);
            border: 2px solid #ff0000;
            color: #ff0000;
            box-shadow: 0 0 30px rgba(255, 0, 0, 0.3);
        }
        
        .status-message {
            margin-top: 15px;
            font-size: 1.1rem;
            opacity: 0.8;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--neon-primary);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
        }
        
        .stat-card:nth-child(2) { animation-delay: 0.5s; }
        .stat-card:nth-child(3) { animation-delay: 1s; }
        .stat-card:nth-child(4) { animation-delay: 1.5s; }
        
        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--glow-primary);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 900;
            color: var(--neon-primary);
        }
        
        .stat-label {
            font-size: 0.8rem;
            opacity: 0.7;
            margin-top: 5px;
        }
        
        .stat-icon {
            font-size: 2rem;
            color: var(--neon-secondary);
            margin-bottom: 10px;
        }
        
        .uptime-container {
            background: var(--bg-card);
            border: 1px solid var(--neon-primary);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .uptime-bar {
            width: 100%;
            height: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }
        
        .uptime-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--neon-primary), var(--neon-secondary));
            border-radius: 10px;
            transition: width 1s ease;
            position: relative;
            animation: glowPulse 2s infinite;
        }
        
        .uptime-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .uptime-label {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 0.9rem;
        }
        
        .ddos-protection {
            background: var(--bg-card);
            border: 2px solid var(--neon-secondary);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .ddos-protection::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(from 0deg, transparent, rgba(255, 0, 255, 0.1), transparent, rgba(0, 240, 255, 0.1), transparent);
            animation: rotate 10s linear infinite;
        }
        
        @keyframes rotate {
            100% { transform: rotate(360deg); }
        }
        
        .ddos-protection .content {
            position: relative;
            z-index: 1;
        }
        
        .ddos-status {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: rgba(0, 255, 0, 0.05);
            border-radius: 10px;
            margin-top: 15px;
        }
        
        .ddos-status .shield {
            font-size: 2rem;
            color: #00ff00;
            animation: shieldPulse 1.5s infinite;
        }
        
        @keyframes shieldPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }
        
        .ddos-status .status-text {
            flex: 1;
        }
        
        .ddos-status .status-text .title {
            font-weight: bold;
            color: #00ff00;
        }
        
        .ddos-status .status-text .subtitle {
            font-size: 0.8rem;
            opacity: 0.7;
        }
        
        .ddos-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .ddos-stat {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }
        
        .ddos-stat .number {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--neon-primary);
        }
        
        .ddos-stat .label {
            font-size: 0.7rem;
            opacity: 0.6;
        }
        
        .ddos-stat.blocked .number { color: #ff4444; }
        .ddos-stat.allowed .number { color: #00ff00; }
        
        .fake-ip-container {
            background: rgba(0, 240, 255, 0.03);
            border: 1px solid rgba(0, 240, 255, 0.2);
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .fake-ip-container .ip-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .fake-ip-container .ip-item {
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 5px;
            font-size: 0.8rem;
            font-family: monospace;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .fake-ip-container .ip-item .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }
        
        .fake-ip-container .ip-item .status-dot.online {
            background: #00ff00;
        }
        
        .fake-ip-container .ip-item .status-dot.blocked {
            background: #ff0000;
        }
        
        .fake-ip-container .ip-item .status-dot.monitoring {
            background: #ffaa00;
        }
        
        .fake-loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.1);
            border-radius: 50%;
            border-top-color: var(--neon-primary);
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            100% { transform: rotate(360deg); }
        }
        
        .last-updated {
            text-align: center;
            font-size: 0.8rem;
            opacity: 0.5;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="neon-container status-container">
        <header class="neon-header">
            <h1 class="neon-title">📊 System Status</h1>
            <nav class="neon-nav">
                <a href="index.php" class="neon-btn neon-btn-small"><i class="fas fa-home"></i> Home</a>
                <a href="spotikufal.php" class="neon-btn neon-btn-small"><i class="fas fa-music"></i> Music</a>
                <?php if (isLoggedIn()): ?>
                    <a href="logout.php" class="neon-btn neon-btn-small"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php endif; ?>
            </nav>
        </header>

        <!-- Status Header -->
        <div class="status-header">
            <div class="status-badge status-<?php echo $status_data['status']; ?>">
                <i class="fas fa-<?php echo $status_data['status'] === 'online' ? 'check-circle' : ($status_data['status'] === 'maintenance' ? 'tools' : 'times-circle'); ?>"></i>
                <?php echo strtoupper($status_data['status']); ?>
            </div>
            <div class="status-message"><?php echo htmlspecialchars($status_data['message']); ?></div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-number" id="active-users"><?php echo $active_users; ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-signal"></i></div>
                <div class="stat-number" id="uptime-percentage"><?php echo $uptime_percentage; ?>%</div>
                <div class="stat-label">Uptime (30 Days)</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-arrow-right"></i></div>
                <div class="stat-number" id="total-requests"><?php echo number_format($total_requests); ?></div>
                <div class="stat-label">Total Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-shield-alt"></i></div>
                <div class="stat-number" id="blocked-requests"><?php echo number_format($blocked_requests); ?></div>
                <div class="stat-label">Threats Blocked</div>
            </div>
        </div>

        <!-- Uptime -->
        <div class="uptime-container">
            <h3><i class="fas fa-chart-line"></i> System Uptime</h3>
            <div class="uptime-bar">
                <div class="uptime-fill" style="width: <?php echo $uptime_percentage; ?>%;"></div>
            </div>
            <div class="uptime-label">
                <span><?php echo $uptime_percentage; ?>% Online</span>
                <span><?php echo $total_days - $available_days; ?> days downtime</span>
            </div>
        </div>

        <!-- Fake DDoS Protection -->
        <div class="ddos-protection">
            <div class="content">
                <h2><i class="fas fa-shield-virus"></i> DDoS Protection</h2>
                <div class="ddos-status">
                    <div class="shield"><i class="fas fa-shield-alt"></i></div>
                    <div class="status-text">
                        <div class="title">🛡️ Protection Active</div>
                        <div class="subtitle">Monitoring <?php echo number_format($total_requests); ?> requests • <?php echo number_format($blocked_requests); ?> threats neutralized</div>
                    </div>
                    <div class="fake-loading"></div>
                </div>
                
                <div class="ddos-stats">
                    <div class="ddos-stat blocked">
                        <div class="number" id="fake-blocked"><?php echo rand(150, 500); ?></div>
                        <div class="label">Threats Blocked</div>
                    </div>
                    <div class="ddos-stat allowed">
                        <div class="number" id="fake-allowed"><?php echo rand(1000, 5000); ?></div>
                        <div class="label">Requests Allowed</div>
                    </div>
                    <div class="ddos-stat">
                        <div class="number" id="fake-attackers"><?php echo rand(5, 50); ?></div>
                        <div class="label">Attackers Detected</div>
                    </div>
                    <div class="ddos-stat">
                        <div class="number" id="fake-protection-level"><?php echo rand(95, 99); ?>%</div>
                        <div class="label">Protection Level</div>
                    </div>
                </div>

                <div class="fake-ip-container">
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
                        <span><i class="fas fa-globe"></i> Live Attackers (Fake Data)</span>
                        <span style="font-size:0.7rem;opacity:0.6;">
                            <i class="fas fa-sync-alt fa-spin"></i> Auto-updating
                        </span>
                    </div>
                    <div class="ip-list" id="ip-list">
                        <!-- JavaScript will populate this -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Last Updated -->
        <div class="last-updated">
            <i class="fas fa-clock"></i> Last updated: <span id="last-updated"><?php echo date('Y-m-d H:i:s'); ?></span>
        </div>
    </div>

    <script>
    // Fake DDoS Protection Script
    (function() {
        const ipList = document.getElementById('ip-list');
        const activeUsersEl = document.getElementById('active-users');
        const blockedEl = document.getElementById('fake-blocked');
        const allowedEl = document.getElementById('fake-allowed');
        const attackersEl = document.getElementById('fake-attackers');
        const protectionEl = document.getElementById('fake-protection-level');
        const lastUpdated = document.getElementById('last-updated');

        // Generate random IP
        function generateIP() {
            return `${rand(10, 255)}.${rand(10, 255)}.${rand(10, 255)}.${rand(10, 255)}`;
        }

        function rand(min, max) {
            return Math.floor(Math.random() * (max - min + 1)) + min;
        }

        // Generate fake IPs
        function generateIPs(count = 15) {
            const ips = [];
            const statuses = ['online', 'blocked', 'monitoring'];
            const countries = ['US', 'CN', 'RU', 'BR', 'IN', 'GB', 'DE', 'FR', 'JP', 'AU', 'NG', 'ZA'];
            
            for (let i = 0; i < count; i++) {
                const status = statuses[rand(0, 2)];
                const blocked = status === 'blocked';
                ips.push({
                    ip: generateIP(),
                    status: status,
                    country: countries[rand(0, countries.length - 1)],
                    blocked: blocked
                });
            }
            return ips;
        }

        // Render IP list
        function renderIPs() {
            const ips = generateIPs(rand(10, 20));
            ipList.innerHTML = '';
            
            ips.forEach(item => {
                const div = document.createElement('div');
                div.className = 'ip-item';
                div.innerHTML = `
                    <span>
                        <span class="status-dot ${item.status}"></span>
                        ${item.ip}
                    </span>
                    <span style="font-size:0.7rem;opacity:0.6;">
                        ${item.country} ${item.blocked ? '🚫' : '✅'}
                    </span>
                `;
                ipList.appendChild(div);
            });
        }

        // Update stats with animation
        function updateStats() {
            // Active users
            const newActive = rand(5, 50);
            animateNumber(activeUsersEl, parseInt(activeUsersEl.textContent) || 0, newActive);
            
            // Blocked
            const newBlocked = parseInt(blockedEl.textContent) + rand(1, 10);
            animateNumber(blockedEl, parseInt(blockedEl.textContent) || 0, newBlocked);
            
            // Allowed
            const newAllowed = parseInt(allowedEl.textContent) + rand(5, 50);
            animateNumber(allowedEl, parseInt(allowedEl.textContent) || 0, newAllowed);
            
            // Attackers
            const newAttackers = rand(5, 50);
            animateNumber(attackersEl, parseInt(attackersEl.textContent) || 0, newAttackers);
            
            // Protection level
            const newProtection = rand(95, 99);
            protectionEl.textContent = newProtection + '%';
            
            // Last updated
            lastUpdated.textContent = new Date().toLocaleString();
            
            // Update IP list
            renderIPs();
        }

        // Animate number
        function animateNumber(element, start, end) {
            const duration = 500;
            const startTime = performance.now();
            
            function update(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                const current = Math.round(start + (end - start) * eased);
                
                element.textContent = current.toLocaleString();
                
                if (progress < 1) {
                    requestAnimationFrame(update);
                }
            }
            
            requestAnimationFrame(update);
        }

        // Initial render
        renderIPs();

        // Update every 3 seconds
        setInterval(updateStats, 3000);

        // Also update active users from real data periodically
        setInterval(function() {
            fetch('api/stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.active_users !== undefined) {
                        animateNumber(activeUsersEl, parseInt(activeUsersEl.textContent) || 0, data.active_users);
                    }
                })
                .catch(() => {});
        }, 10000);
    })();
    </script>
</body>
</html>