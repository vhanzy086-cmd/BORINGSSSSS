<?php
require_once '../config.php';

if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit();
}

$pdo = getDB();

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['music_file'])) {
    $title = $_POST['title'] ?? 'Unknown';
    $artist = $_POST['artist'] ?? 'Unknown';
    $album = $_POST['album'] ?? '';
    $genre = $_POST['genre'] ?? '';
    $release_date = $_POST['release_date'] ?? null;
    $duration = (int)($_POST['duration'] ?? 0);
    $lyrics = $_POST['lyrics'] ?? '';
    
    $upload_dir = '../uploads/music/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_name = time() . '_' . basename($_FILES['music_file']['name']);
    $file_path = $upload_dir . $file_name;
    
    if (move_uploaded_file($_FILES['music_file']['tmp_name'], $file_path)) {
        $stmt = $pdo->prepare("
            INSERT INTO music (title, artist, album, genre, release_date, duration, lyrics, file_path, uploaded_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $title, $artist, $album, $genre, $release_date, 
            $duration, $lyrics, $file_path, $_SESSION['admin_id']
        ]);
        
        logAction($_SESSION['admin_id'], 'upload_music', "Uploaded: $title");
        $message = "Music uploaded successfully!";
    } else {
        $error = "Failed to upload file";
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT file_path FROM music WHERE id = ?");
    $stmt->execute([$id]);
    $song = $stmt->fetch();
    
    if ($song && file_exists($song['file_path'])) {
        unlink($song['file_path']);
    }
    
    $stmt = $pdo->prepare("DELETE FROM music WHERE id = ?");
    $stmt->execute([$id]);
    
    $message = "Song deleted";
}

// Get songs
$stmt = $pdo->query("
    SELECT m.*, u.username as uploader 
    FROM music m 
    LEFT JOIN users u ON m.uploaded_by = u.id 
    ORDER BY m.created_at DESC
");
$songs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Music - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-message {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid #00ff00;
            border-radius: 10px;
            padding: 10px 15px;
            margin-bottom: 20px;
            color: #66ff66;
        }
        .admin-error {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid #ff0000;
            border-radius: 10px;
            padding: 10px 15px;
            margin-bottom: 20px;
            color: #ff6666;
        }
        .admin-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            max-width: 800px;
        }
        .admin-form .full-width {
            grid-column: 1 / -1;
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
        .btn-danger {
            background: transparent;
            border: 1px solid #ff0000;
            color: #ff0000;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
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
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="neon-container">
        <header class="neon-header">
            <h1 class="neon-title">🎵 Manage Music</h1>
            <nav class="neon-nav">
                <a href="../index.php" class="neon-btn neon-btn-small"><i class="fas fa-home"></i> Home</a>
                <a href="dashboard.php" class="neon-btn neon-btn-small"><i class="fas fa-shield-alt"></i> Dashboard</a>
                <a href="../logout.php" class="neon-btn neon-btn-small"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </header>

        <div class="neon-card">
            <div class="card-header">
                <h2><i class="fas fa-upload"></i> Upload Music</h2>
                <div class="neon-border"></div>
            </div>
            <div class="card-body">
                <?php if (isset($message)): ?>
                    <div class="admin-message"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="admin-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data" class="admin-form">
                    <div class="form-group">
                        <label for="title">Song Title *</label>
                        <input type="text" id="title" name="title" class="neon-input" required>
                    </div>
                    <div class="form-group">
                        <label for="artist">Artist *</label>
                        <input type="text" id="artist" name="artist" class="neon-input" required>
                    </div>
                    <div class="form-group">
                        <label for="album">Album</label>
                        <input type="text" id="album" name="album" class="neon-input">
                    </div>
                    <div class="form-group">
                        <label for="genre">Genre</label>
                        <input type="text" id="genre" name="genre" class="neon-input" placeholder="e.g., Pop, Rock, EDM">
                    </div>
                    <div class="form-group">
                        <label for="release_date">Release Date</label>
                        <input type="date" id="release_date" name="release_date" class="neon-input">
                    </div>
                    <div class="form-group">
                        <label for="duration">Duration (seconds)</label>
                        <input type="number" id="duration" name="duration" class="neon-input" placeholder="e.g., 180">
                    </div>
                    <div class="form-group full-width">
                        <label for="lyrics">Lyrics</label>
                        <textarea id="lyrics" name="lyrics" class="neon-textarea" rows="4" placeholder="Enter song lyrics..."></textarea>
                    </div>
                    <div class="form-group full-width">
                        <label for="music_file">MP3 File *</label>
                        <input type="file" id="music_file" name="music_file" accept=".mp3,.mp4" class="neon-input" required>
                    </div>
                    <div class="form-group full-width">
                        <button type="submit" class="neon-btn">
                            <i class="fas fa-upload"></i> Upload Song
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="neon-card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> Music Library</h2>
                <div class="neon-border"></div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Artist</th>
                                <th>Album</th>
                                <th>Genre</th>
                                <th>Plays</th>
                                <th>Likes</th>
                                <th>Uploaded</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($songs as $song): ?>
                            <tr>
                                <td><?php echo $song['id']; ?></td>
                                <td><?php echo htmlspecialchars($song['title']); ?></td>
                                <td><?php echo htmlspecialchars($song['artist'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($song['album'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($song['genre'] ?? '-'); ?></td>
                                <td><?php echo number_format($song['plays']); ?></td>
                                <td><?php echo number_format($song['likes']); ?></td>
                                <td><?php echo htmlspecialchars($song['uploader'] ?? 'Unknown'); ?></td>
                                <td>
                                    <a href="?delete=<?php echo $song['id']; ?>" class="btn-danger" onclick="return confirm('Delete this song?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>