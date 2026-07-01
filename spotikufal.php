<?php
require_once 'config.php';

$pdo = getDB();

// Get all songs with metadata
$stmt = $pdo->query("
    SELECT m.*, u.username as uploader 
    FROM music m 
    LEFT JOIN users u ON m.uploaded_by = u.id 
    ORDER BY m.plays DESC, m.created_at DESC
");
$songs = $stmt->fetchAll();

// Get featured song (most played)
$stmt = $pdo->query("
    SELECT * FROM music 
    ORDER BY plays DESC, likes DESC 
    LIMIT 1
");
$featured = $stmt->fetch();

// Get total songs
$stmt = $pdo->query("SELECT COUNT(*) as count FROM music");
$total_songs = $stmt->fetch()['count'];

// Get total plays
$stmt = $pdo->query("SELECT SUM(plays) as total FROM music");
$total_plays = $stmt->fetch()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎵 Spotikufal Mosik - Neon Generator</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Spotikufal Styles */
        .spotikufal-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .music-header {
            text-align: center;
            padding: 30px 20px;
            background: linear-gradient(135deg, rgba(0,240,255,0.1), rgba(255,0,255,0.1));
            border-radius: 20px;
            margin-bottom: 30px;
            border: 1px solid var(--neon-primary);
        }
        
        .music-header h1 {
            font-size: 2.5rem;
            background: linear-gradient(90deg, var(--neon-primary), var(--neon-secondary), var(--neon-primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: neonPulse 2s infinite;
        }
        
        .music-header .subtitle {
            font-size: 1rem;
            opacity: 0.7;
            margin-top: 5px;
        }
        
        /* Featured Player */
        .featured-player {
            background: var(--bg-card);
            border: 2px solid var(--neon-secondary);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .featured-player::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(from 0deg, transparent, rgba(255,0,255,0.05), transparent, rgba(0,240,255,0.05), transparent);
            animation: rotate 15s linear infinite;
        }
        
        .featured-player .content {
            position: relative;
            z-index: 1;
        }
        
        .featured-player .featured-badge {
            display: inline-block;
            padding: 5px 15px;
            background: var(--neon-secondary);
            color: #fff;
            border-radius: 20px;
            font-size: 0.7rem;
            margin-bottom: 15px;
        }
        
        .featured-player .song-info {
            display: flex;
            align-items: center;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        .featured-player .album-art {
            width: 150px;
            height: 150px;
            border-radius: 15px;
            background: linear-gradient(135deg, var(--neon-primary), var(--neon-secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: #fff;
            box-shadow: var(--glow-primary);
            animation: float 3s ease-in-out infinite;
        }
        
        .featured-player .song-details {
            flex: 1;
        }
        
        .featured-player .song-details .title {
            font-size: 1.8rem;
            color: var(--neon-primary);
        }
        
        .featured-player .song-details .artist {
            font-size: 1.2rem;
            opacity: 0.7;
        }
        
        .featured-player .song-details .meta {
            display: flex;
            gap: 20px;
            margin-top: 10px;
            font-size: 0.8rem;
            opacity: 0.6;
        }
        
        /* Music Grid */
        .music-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .music-card {
            background: var(--bg-card);
            border: 1px solid var(--neon-primary);
            border-radius: 15px;
            padding: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .music-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.05), transparent);
            transition: left 0.5s ease;
        }
        
        .music-card:hover::before {
            left: 100%;
        }
        
        .music-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: var(--glow-primary);
            border-color: var(--neon-secondary);
        }
        
        .music-card .card-art {
            width: 100%;
            height: 150px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--neon-primary), var(--neon-secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #fff;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .music-card:hover .card-art {
            transform: scale(1.05);
        }
        
        .music-card .card-title {
            font-size: 1rem;
            color: var(--neon-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .music-card .card-artist {
            font-size: 0.8rem;
            opacity: 0.7;
        }
        
        .music-card .card-meta {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 0.7rem;
            opacity: 0.6;
        }
        
        .music-card .card-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .music-card .card-actions button {
            flex: 1;
            padding: 8px;
            border: 1px solid var(--neon-primary);
            background: transparent;
            color: var(--neon-primary);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Orbitron', sans-serif;
            font-size: 0.7rem;
        }
        
        .music-card .card-actions button:hover {
            background: var(--neon-primary);
            color: var(--bg-dark);
            box-shadow: var(--glow-primary);
        }
        
        .music-card .card-actions .play-btn {
            border-color: var(--neon-secondary);
            color: var(--neon-secondary);
        }
        
        .music-card .card-actions .play-btn:hover {
            background: var(--neon-secondary);
            color: var(--bg-dark);
            box-shadow: var(--glow-secondary);
        }
        
        /* Now Playing Bar */
        .now-playing-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--bg-card);
            border-top: 2px solid var(--neon-primary);
            padding: 15px 30px;
            display: none;
            align-items: center;
            gap: 20px;
            backdrop-filter: blur(20px);
            z-index: 1000;
            box-shadow: 0 -10px 40px rgba(0,0,0,0.5);
        }
        
        .now-playing-bar.show {
            display: flex;
        }
        
        .now-playing-bar .mini-art {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--neon-primary), var(--neon-secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #fff;
            flex-shrink: 0;
        }
        
        .now-playing-bar .mini-info {
            flex: 1;
            min-width: 100px;
        }
        
        .now-playing-bar .mini-info .mini-title {
            font-size: 0.9rem;
            color: var(--neon-primary);
        }
        
        .now-playing-bar .mini-info .mini-artist {
            font-size: 0.7rem;
            opacity: 0.7;
        }
        
        .now-playing-bar .mini-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .now-playing-bar .mini-controls button {
            background: transparent;
            border: none;
            color: var(--neon-primary);
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 5px 10px;
            border-radius: 50%;
        }
        
        .now-playing-bar .mini-controls button:hover {
            background: rgba(0, 240, 255, 0.1);
            transform: scale(1.1);
        }
        
        .now-playing-bar .mini-controls .play-toggle {
            font-size: 1.5rem;
            border: 2px solid var(--neon-primary);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .now-playing-bar .mini-controls .play-toggle:hover {
            background: var(--neon-primary);
            color: var(--bg-dark);
        }
        
        .now-playing-bar .progress-container {
            flex: 2;
            min-width: 150px;
        }
        
        .now-playing-bar .progress-container .progress-bar {
            width: 100%;
            height: 4px;
            background: rgba(255,255,255,0.1);
            border-radius: 2px;
            cursor: pointer;
            position: relative;
        }
        
        .now-playing-bar .progress-container .progress-bar .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--neon-primary), var(--neon-secondary));
            border-radius: 2px;
            width: 0%;
            transition: width 0.1s linear;
            position: relative;
        }
        
        .now-playing-bar .progress-container .progress-bar .progress-fill::after {
            content: '';
            position: absolute;
            right: -6px;
            top: -4px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--neon-secondary);
            box-shadow: var(--glow-secondary);
        }
        
        .now-playing-bar .time-display {
            font-size: 0.7rem;
            opacity: 0.6;
            min-width: 80px;
            text-align: center;
        }
        
        .now-playing-bar .volume-control {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 120px;
        }
        
        .now-playing-bar .volume-control input[type="range"] {
            flex: 1;
            -webkit-appearance: none;
            height: 4px;
            background: rgba(255,255,255,0.1);
            border-radius: 2px;
            outline: none;
        }
        
        .now-playing-bar .volume-control input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--neon-primary);
            cursor: pointer;
            box-shadow: var(--glow-primary);
        }
        
        .now-playing-bar .close-player {
            color: #ff4444;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            padding: 5px;
        }
        
        .now-playing-bar .close-player:hover {
            transform: rotate(90deg);
        }
        
        /* Loading animation for music */
        .music-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
        }
        
        .music-loading .loader {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 4px solid rgba(255,255,255,0.1);
            border-top-color: var(--neon-primary);
            border-bottom-color: var(--neon-secondary);
            animation: spin 1s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite;
        }
        
        .music-loading .loader-text {
            margin-left: 20px;
            font-size: 1.2rem;
            animation: neonPulse 1.5s infinite;
        }
        
        /* Volume animation */
        .volume-animation {
            display: none;
            position: fixed;
            bottom: 100px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--bg-card);
            border: 2px solid var(--neon-primary);
            border-radius: 20px;
            padding: 20px 30px;
            z-index: 999;
            min-width: 200px;
            text-align: center;
        }
        
        .volume-animation.show {
            display: block;
            animation: slideUp 0.3s ease;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateX(-50%) translateY(20px); }
            to { opacity: 1; transform: translateX(-50%) translateY(0); }
        }
        
        .volume-animation .volume-bar {
            width: 100%;
            height: 6px;
            background: rgba(255,255,255,0.1);
            border-radius: 3px;
            margin-top: 10px;
            overflow: hidden;
        }
        
        .volume-animation .volume-bar .volume-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--neon-primary), var(--neon-secondary));
            border-radius: 3px;
            transition: width 0.1s linear;
        }
        
        .volume-animation .volume-icon {
            font-size: 2rem;
        }
        
        /* Timer animation for music */
        .timer-display {
            font-family: 'Orbitron', monospace;
            font-size: 0.9rem;
            color: var(--neon-primary);
            min-width: 80px;
            text-align: center;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .now-playing-bar {
                flex-wrap: wrap;
                padding: 10px 15px;
            }
            
            .now-playing-bar .mini-info {
                min-width: 80px;
            }
            
            .now-playing-bar .progress-container {
                flex: 1 1 100%;
                order: 10;
            }
            
            .now-playing-bar .volume-control {
                min-width: 80px;
            }
            
            .featured-player .song-info {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .featured-player .album-art {
                width: 100px;
                height: 100px;
            }
            
            .music-grid {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            }
            
            .music-header h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="neon-container spotikufal-container">
        <header class="neon-header">
            <h1 class="neon-title">🎵 Spotikufal Mosik</h1>
            <nav class="neon-nav">
                <a href="index.php" class="neon-btn neon-btn-small"><i class="fas fa-home"></i> Home</a>
                <a href="status.php" class="neon-btn neon-btn-small"><i class="fas fa-chart-line"></i> Status</a>
                <?php if (isLoggedIn()): ?>
                    <a href="logout.php" class="neon-btn neon-btn-small"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php endif; ?>
            </nav>
        </header>

        <!-- Music Header -->
        <div class="music-header">
            <h1>🎵 Spotikufal Mosik</h1>
            <div class="subtitle">
                <i class="fas fa-music"></i> <?php echo $total_songs; ?> songs • 
                <i class="fas fa-headphones"></i> <?php echo number_format($total_plays); ?> total plays
            </div>
        </div>

        <!-- Featured Player -->
        <?php if ($featured): ?>
        <div class="featured-player">
            <div class="content">
                <div class="featured-badge"><i class="fas fa-star"></i> Featured</div>
                <div class="song-info">
                    <div class="album-art">
                        <i class="fas fa-music"></i>
                    </div>
                    <div class="song-details">
                        <div class="title"><?php echo htmlspecialchars($featured['title']); ?></div>
                        <div class="artist"><?php echo htmlspecialchars($featured['artist'] ?? 'Unknown Artist'); ?></div>
                        <div class="meta">
                            <span><i class="fas fa-headphones"></i> <?php echo number_format($featured['plays']); ?> plays</span>
                            <span><i class="fas fa-heart"></i> <?php echo number_format($featured['likes']); ?> likes</span>
                            <?php if ($featured['genre']): ?>
                            <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($featured['genre']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top:15px;">
                            <button class="neon-btn" onclick="playSong(<?php echo $featured['id']; ?>)">
                                <i class="fas fa-play"></i> Play Now
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Music Grid -->
        <h2 style="margin-bottom:20px;"><i class="fas fa-list"></i> All Songs</h2>
        <div class="music-grid" id="music-grid">
            <?php if (empty($songs)): ?>
                <div style="grid-column:1/-1;text-align:center;padding:60px 20px;">
                    <i class="fas fa-music fa-3x" style="color:var(--neon-primary);margin-bottom:20px;display:block;"></i>
                    <p>No songs available yet. Check back later!</p>
                </div>
            <?php else: ?>
                <?php foreach ($songs as $song): ?>
                <div class="music-card" data-id="<?php echo $song['id']; ?>">
                    <div class="card-art">
                        <i class="fas fa-music"></i>
                    </div>
                    <div class="card-title"><?php echo htmlspecialchars($song['title']); ?></div>
                    <div class="card-artist"><?php echo htmlspecialchars($song['artist'] ?? 'Unknown Artist'); ?></div>
                    <div class="card-meta">
                        <span><i class="fas fa-headphones"></i> <?php echo number_format($song['plays']); ?></span>
                        <span><i class="fas fa-heart"></i> <?php echo number_format($song['likes']); ?></span>
                        <?php if ($song['duration']): ?>
                        <span><i class="fas fa-clock"></i> <?php echo gmdate('i:s', $song['duration']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="card-actions">
                        <button class="play-btn" onclick="playSong(<?php echo $song['id']; ?>)">
                            <i class="fas fa-play"></i> Play
                        </button>
                        <button onclick="likeSong(<?php echo $song['id']; ?>, this)">
                            <i class="fas fa-heart"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Now Playing Bar -->
    <div class="now-playing-bar" id="now-playing-bar">
        <div class="mini-art">
            <i class="fas fa-music"></i>
        </div>
        <div class="mini-info">
            <div class="mini-title" id="now-title">No song playing</div>
            <div class="mini-artist" id="now-artist">-</div>
        </div>
        <div class="mini-controls">
            <button onclick="prevSong()"><i class="fas fa-step-backward"></i></button>
            <button class="play-toggle" onclick="togglePlay()">
                <i class="fas fa-play" id="play-icon"></i>
            </button>
            <button onclick="nextSong()"><i class="fas fa-step-forward"></i></button>
        </div>
        <div class="progress-container">
            <div class="timer-display" id="current-time">0:00</div>
            <div class="progress-bar" onclick="seekProgress(event)">
                <div class="progress-fill" id="progress-fill"></div>
            </div>
            <div class="timer-display" id="total-time">0:00</div>
        </div>
        <div class="volume-control">
            <i class="fas fa-volume-up" onclick="toggleMute()"></i>
            <input type="range" id="volume-slider" min="0" max="100" value="80" oninput="updateVolume(this.value)">
        </div>
        <div class="close-player" onclick="closePlayer()">
            <i class="fas fa-times"></i>
        </div>
    </div>

    <!-- Volume Animation -->
    <div class="volume-animation" id="volume-animation">
        <div class="volume-icon" id="volume-icon"><i class="fas fa-volume-up"></i></div>
        <div class="volume-bar">
            <div class="volume-fill" id="volume-fill" style="width:80%;"></div>
        </div>
        <div style="margin-top:5px;font-size:0.8rem;" id="volume-text">80%</div>
    </div>

    <script>
    // Spotikufal Music Player
    (function() {
        // State
        let currentSong = null;
        let playlist = [];
        let currentIndex = -1;
        let isPlaying = false;
        let audio = new Audio();
        let isMuted = false;
        let previousVolume = 80;
        
        // DOM Elements
        const bar = document.getElementById('now-playing-bar');
        const nowTitle = document.getElementById('now-title');
        const nowArtist = document.getElementById('now-artist');
        const playIcon = document.getElementById('play-icon');
        const progressFill = document.getElementById('progress-fill');
        const currentTimeEl = document.getElementById('current-time');
        const totalTimeEl = document.getElementById('total-time');
        const volumeSlider = document.getElementById('volume-slider');
        const volumeAnimation = document.getElementById('volume-animation');
        const volumeFill = document.getElementById('volume-fill');
        const volumeText = document.getElementById('volume-text');
        const volumeIcon = document.getElementById('volume-icon');
        
        // Get playlist from PHP
        <?php
        $playlist_data = [];
        foreach ($songs as $song) {
            $playlist_data[] = [
                'id' => $song['id'],
                'title' => $song['title'],
                'artist' => $song['artist'] ?? 'Unknown',
                'file' => $song['file_path'],
                'duration' => $song['duration'] ?? 0
            ];
        }
        ?>
        playlist = <?php echo json_encode($playlist_data); ?>;
        
        // Load song
        function loadSong(index) {
            if (index < 0 || index >= playlist.length) return;
            
            const song = playlist[index];
            currentSong = song;
            currentIndex = index;
            
            audio.src = song.file;
            audio.load();
            
            nowTitle.textContent = song.title;
            nowArtist.textContent = song.artist;
            
            // Update total time
            if (song.duration) {
                totalTimeEl.textContent = formatTime(song.duration);
            } else {
                audio.addEventListener('loadedmetadata', function() {
                    totalTimeEl.textContent = formatTime(audio.duration);
                }, { once: true });
            }
            
            bar.classList.add('show');
            
            // Play if was playing
            if (isPlaying) {
                audio.play();
            }
        }
        
        // Play song
        window.playSong = function(id) {
            const index = playlist.findIndex(s => s.id == id);
            if (index === -1) return;
            
            // If same song, toggle
            if (currentIndex === index) {
                togglePlay();
                return;
            }
            
            loadSong(index);
            audio.play();
            isPlaying = true;
            playIcon.className = 'fas fa-pause';
            
            // Update plays
            fetch('api/music_play.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ music_id: id })
            }).catch(() => {});
        };
        
        // Toggle play
        window.togglePlay = function() {
            if (!currentSong) {
                if (playlist.length > 0) {
                    loadSong(0);
                    audio.play();
                    isPlaying = true;
                    playIcon.className = 'fas fa-pause';
                }
                return;
            }
            
            if (isPlaying) {
                audio.pause();
                isPlaying = false;
                playIcon.className = 'fas fa-play';
            } else {
                audio.play();
                isPlaying = true;
                playIcon.className = 'fas fa-pause';
            }
        };
        
        // Next song
        window.nextSong = function() {
            if (playlist.length === 0) return;
            const next = (currentIndex + 1) % playlist.length;
            loadSong(next);
            if (isPlaying) {
                audio.play();
            }
        };
        
        // Previous song
        window.prevSong = function() {
            if (playlist.length === 0) return;
            const prev = (currentIndex - 1 + playlist.length) % playlist.length;
            loadSong(prev);
            if (isPlaying) {
                audio.play();
            }
        };
        
        // Like song
        window.likeSong = function(id, btn) {
            fetch('api/music_like.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ music_id: id })
            }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    btn.style.color = '#ff0066';
                    btn.style.transform = 'scale(1.3)';
                    setTimeout(() => {
                        btn.style.transform = 'scale(1)';
                    }, 300);
                }
            }).catch(() => {});
        };
        
        // Seek progress
        window.seekProgress = function(e) {
            if (!audio.duration) return;
            const rect = e.currentTarget.getBoundingClientRect();
            const pos = (e.clientX - rect.left) / rect.width;
            audio.currentTime = pos * audio.duration;
        };
        
        // Update volume
        window.updateVolume = function(value) {
            const vol = parseInt(value);
            audio.volume = vol / 100;
            
            // Update animation
            volumeFill.style.width = vol + '%';
            volumeText.textContent = vol + '%';
            
            // Update icon
            if (vol === 0) {
                volumeIcon.innerHTML = '<i class="fas fa-volume-mute"></i>';
            } else if (vol < 50) {
                volumeIcon.innerHTML = '<i class="fas fa-volume-down"></i>';
            } else {
                volumeIcon.innerHTML = '<i class="fas fa-volume-up"></i>';
            }
            
            // Show animation
            volumeAnimation.classList.add('show');
            clearTimeout(window.volumeTimeout);
            window.volumeTimeout = setTimeout(() => {
                volumeAnimation.classList.remove('show');
            }, 1500);
        };
        
        // Toggle mute
        window.toggleMute = function() {
            isMuted = !isMuted;
            if (isMuted) {
                previousVolume = audio.volume * 100 || 80;
                volumeSlider.value = 0;
                updateVolume(0);
            } else {
                volumeSlider.value = previousVolume;
                updateVolume(previousVolume);
            }
        };
        
        // Close player
        window.closePlayer = function() {
            audio.pause();
            isPlaying = false;
            bar.classList.remove('show');
            currentSong = null;
        };
        
        // Format time
        function formatTime(seconds) {
            if (!seconds || isNaN(seconds)) return '0:00';
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        }
        
        // Audio events
        audio.addEventListener('timeupdate', function() {
            if (this.duration) {
                const progress = (this.currentTime / this.duration) * 100;
                progressFill.style.width = progress + '%';
                currentTimeEl.textContent = formatTime(this.currentTime);
            }
        });
        
        audio.addEventListener('ended', function() {
            nextSong();
        });
        
        audio.addEventListener('error', function(e) {
            console.error('Audio error:', e);
            // Try next song
            nextSong();
        });
        
        // Initialize volume
        audio.volume = 0.8;
        volumeSlider.value = 80;
        volumeFill.style.width = '80%';
        volumeText.textContent = '80%';
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
            
            if (e.code === 'Space') {
                e.preventDefault();
                togglePlay();
            }
            if (e.code === 'ArrowRight') {
                e.preventDefault();
                if (audio.duration) {
                    audio.currentTime = Math.min(audio.currentTime + 10, audio.duration);
                }
            }
            if (e.code === 'ArrowLeft') {
                e.preventDefault();
                if (audio.duration) {
                    audio.currentTime = Math.max(audio.currentTime - 10, 0);
                }
            }
            if (e.code === 'ArrowUp') {
                e.preventDefault();
                const newVol = Math.min(100, parseInt(volumeSlider.value) + 5);
                volumeSlider.value = newVol;
                updateVolume(newVol);
            }
            if (e.code === 'ArrowDown') {
                e.preventDefault();
                const newVol = Math.max(0, parseInt(volumeSlider.value) - 5);
                volumeSlider.value = newVol;
                updateVolume(newVol);
            }
        });
    })();
    </script>
</body>
</html>