// js/script.js
document.addEventListener('DOMContentLoaded', function() {
    // ===================== SLIDER =====================
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.dot');
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');
    let currentSlide = 0;
    let slideInterval;

    function showSlide(index) {
        slides.forEach((slide, i) => {
            slide.classList.toggle('active', i === index);
        });
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });
    }

    function nextSlide() {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    }

    function prevSlide() {
        currentSlide = (currentSlide - 1 + slides.length) % slides.length;
        showSlide(currentSlide);
    }

    function startSlider() {
        slideInterval = setInterval(nextSlide, 5000);
    }

    function stopSlider() {
        clearInterval(slideInterval);
    }

    if (prevBtn && nextBtn) {
        prevBtn.addEventListener('click', () => {
            stopSlider();
            prevSlide();
            startSlider();
        });

        nextBtn.addEventListener('click', () => {
            stopSlider();
            nextSlide();
            startSlider();
        });
    }

    dots.forEach((dot) => {
        dot.addEventListener('click', () => {
            stopSlider();
            const index = parseInt(dot.dataset.index);
            currentSlide = index;
            showSlide(currentSlide);
            startSlider();
        });
    });

    startSlider();

    // ===================== STATS =====================
    function fetchStats() {
        fetch('api/stats.php')
            .then(response => response.json())
            .then(data => {
                document.getElementById('total-users').textContent = data.total_users || 0;
                document.getElementById('total-keys').textContent = data.total_keys || 0;
                document.getElementById('active-users').textContent = data.active_users || 0;
                document.getElementById('total-songs').textContent = data.total_songs || 0;
            })
            .catch(error => console.error('Error fetching stats:', error));
    }

    fetchStats();
    setInterval(fetchStats, 30000); // Refresh every 30 seconds

    // ===================== GENERATOR =====================
    const generateBtn = document.getElementById('generate-btn');
    const domainSelect = document.getElementById('domain-select');
    const countInput = document.getElementById('count-input');
    const resultDiv = document.getElementById('generator-result');

    if (generateBtn) {
        generateBtn.addEventListener('click', function() {
            const domain = domainSelect.value;
            const count = countInput.value || 100;
            
            // Animation feedback
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
            this.disabled = true;
            
            fetch('api/generate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    domain: domain,
                    count: count
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = `
                        <p><i class="fas fa-check-circle" style="color: #00ff00;"></i> Success!</p>
                        <p>Domain: ${data.domain}</p>
                        <p>Generated: ${data.count} accounts</p>
                        <p>Remaining: ${data.remaining}</p>
                        ${data.download ? `<a href="${data.download}" class="neon-btn neon-btn-small">Download</a>` : ''}
                    `;
                    resultDiv.classList.add('show');
                } else {
                    resultDiv.innerHTML = `
                        <p><i class="fas fa-exclamation-circle" style="color: #ff0000;"></i> ${data.message || 'Error generating accounts'}</p>
                    `;
                    resultDiv.classList.add('show');
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `
                    <p><i class="fas fa-exclamation-circle" style="color: #ff0000;"></i> Error: ${error.message}</p>
                `;
                resultDiv.classList.add('show');
            })
            .finally(() => {
                this.innerHTML = '<i class="fas fa-bolt"></i> Generate Now';
                this.disabled = false;
            });
        });
    }

    // ===================== MUSIC PLAYER =====================
    const audioPlayer = new Audio();
    let playlist = [];
    let currentSongIndex = 0;
    let isPlaying = false;

    function loadPlaylist() {
        fetch('api/music.php')
            .then(response => response.json())
            .then(data => {
                playlist = data;
                if (playlist.length > 0) {
                    updatePlayerInfo(0);
                }
            })
            .catch(error => console.error('Error loading playlist:', error));
    }

    function updatePlayerInfo(index) {
        if (playlist[index]) {
            const song = playlist[index];
            document.getElementById('current-song').textContent = song.title || 'Unknown';
            document.getElementById('current-artist').textContent = song.artist || '-';
            audioPlayer.src = song.file_path || '';
            document.getElementById('total-time').textContent = formatTime(song.duration || 0);
        }
    }

    function formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }

    // Play/Pause
    const playBtn = document.getElementById('play-toggle');
    if (playBtn) {
        playBtn.addEventListener('click', function() {
            if (playlist.length === 0) {
                loadPlaylist();
                setTimeout(() => {
                    if (playlist.length > 0) {
                        togglePlay();
                    }
                }, 500);
                return;
            }
            togglePlay();
        });
    }

    function togglePlay() {
        if (isPlaying) {
            audioPlayer.pause();
            playBtn.innerHTML = '<i class="fas fa-play"></i>';
            isPlaying = false;
        } else {
            audioPlayer.play();
            playBtn.innerHTML = '<i class="fas fa-pause"></i>';
            isPlaying = true;
        }
    }

    // Next/Previous
    document.getElementById('next-song')?.addEventListener('click', function() {
        if (playlist.length === 0) return;
        currentSongIndex = (currentSongIndex + 1) % playlist.length;
        updatePlayerInfo(currentSongIndex);
        if (isPlaying) {
            audioPlayer.play();
        }
    });

    document.getElementById('prev-song')?.addEventListener('click', function() {
        if (playlist.length === 0) return;
        currentSongIndex = (currentSongIndex - 1 + playlist.length) % playlist.length;
        updatePlayerInfo(currentSongIndex);
        if (isPlaying) {
            audioPlayer.play();
        }
    });

    // Progress bar
    const progressBar = document.getElementById('progress-bar');
    if (progressBar) {
        audioPlayer.addEventListener('timeupdate', function() {
            if (audioPlayer.duration) {
                const progress = (audioPlayer.currentTime / audioPlayer.duration) * 100;
                progressBar.value = progress;
                document.getElementById('current-time').textContent = formatTime(audioPlayer.currentTime);
            }
        });

        progressBar.addEventListener('input', function() {
            const time = (this.value / 100) * audioPlayer.duration;
            audioPlayer.currentTime = time;
        });
    }

    audioPlayer.addEventListener('ended', function() {
        if (playlist.length > 0) {
            currentSongIndex = (currentSongIndex + 1) % playlist.length;
            updatePlayerInfo(currentSongIndex);
            audioPlayer.play();
        }
    });

    // Load initial playlist
    loadPlaylist();

    // ===================== FEEDBACK =====================
    const feedbackForm = document.getElementById('feedback-form');
    if (feedbackForm) {
        feedbackForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const message = document.getElementById('feedback-message').value.trim();
            
            if (!message) {
                alert('Please enter a message');
                return;
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            submitBtn.disabled = true;

            fetch('api/feedback.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ message: message })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Thank you for your feedback!');
                    document.getElementById('feedback-message').value = '';
                } else {
                    alert('Error: ' + (data.message || 'Could not send feedback'));
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            })
            .finally(() => {
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Feedback';
                submitBtn.disabled = false;
            });
        });
    }

    // ===================== ADMIN FUNCTIONS =====================
    // Add admin-specific functions here
    console.log('Neon Generator loaded!');
});