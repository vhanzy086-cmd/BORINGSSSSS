-- Database: bot_generator

CREATE DATABASE IF NOT EXISTS bot_generator;
USE bot_generator;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    role ENUM('user', 'admin', 'reseller') DEFAULT 'user',
    is_banned BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Add status tables
CREATE TABLE IF NOT EXISTS website_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    status ENUM('online', 'maintenance', 'offline') DEFAULT 'online',
    message TEXT,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add fake DDoS protection logs
CREATE TABLE IF NOT EXISTS ddos_protection_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    request_type VARCHAR(50),
    is_blocked BOOLEAN DEFAULT FALSE,
    threat_level INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add music play history
CREATE TABLE IF NOT EXISTS music_play_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    music_id INT,
    user_id INT,
    ip_address VARCHAR(45),
    played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (music_id) REFERENCES music(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default website status
INSERT INTO website_status (status, message) VALUES ('online', 'All systems operational');

-- Add more music columns
ALTER TABLE music ADD COLUMN duration INT DEFAULT 0;
ALTER TABLE music ADD COLUMN plays INT DEFAULT 0;
ALTER TABLE music ADD COLUMN likes INT DEFAULT 0;
ALTER TABLE music ADD COLUMN album VARCHAR(100);
ALTER TABLE music ADD COLUMN genre VARCHAR(50);
ALTER TABLE music ADD COLUMN release_date DATE;
ALTER TABLE music ADD COLUMN lyrics TEXT;

-- Keys table
CREATE TABLE IF NOT EXISTS keys_table (
    id INT PRIMARY KEY AUTO_INCREMENT,
    key_code VARCHAR(50) UNIQUE NOT NULL,
    key_type VARCHAR(20) NOT NULL,
    expiry DATETIME NULL,
    owner_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    redeemed_at TIMESTAMP NULL,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Logs table
CREATE TABLE IF NOT EXISTS logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100),
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Feedback table
CREATE TABLE IF NOT EXISTS feedback (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    message TEXT,
    status ENUM('pending', 'read', 'resolved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Banned users table
CREATE TABLE IF NOT EXISTS banned_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    reason TEXT,
    banned_by INT,
    banned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (banned_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Music table
CREATE TABLE IF NOT EXISTS music (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    artist VARCHAR(100),
    file_path VARCHAR(255) NOT NULL,
    thumbnail VARCHAR(255),
    duration INT,
    uploaded_by INT,
    plays INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default admin
INSERT INTO users (username, password, email, role) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'admin');
-- Password: password

-- Insert sample keys
INSERT INTO keys_table (key_code, key_type, expiry) VALUES 
('KEY-ABC123', '1d', DATE_ADD(NOW(), INTERVAL 1 DAY)),
('KEY-DEF456', '7d', DATE_ADD(NOW(), INTERVAL 7 DAY)),
('KEY-GHI789', '30d', DATE_ADD(NOW(), INTERVAL 30 DAY));

-- Add feedback table with file support
CREATE TABLE IF NOT EXISTS feedback (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    username VARCHAR(50),
    message TEXT,
    file_path VARCHAR(255),
    file_type VARCHAR(20),
    status ENUM('pending', 'read', 'resolved', 'replied') DEFAULT 'pending',
    admin_reply TEXT,
    replied_by INT,
    replied_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (replied_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Add admin_reply_id to track replies
ALTER TABLE feedback ADD COLUMN admin_reply_id INT NULL;
ALTER TABLE feedback ADD COLUMN is_archived BOOLEAN DEFAULT FALSE;

-- Create admin accounts table for secure login
CREATE TABLE IF NOT EXISTS admin_accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Insert default admin
INSERT INTO admin_accounts (username, password, role) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin');
-- Password: password

-- Create settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add Telegram settings
INSERT INTO settings (setting_key, setting_value) VALUES 
('telegram_bot_token', '8224702445:AAFSwKnh7X_mRdN-CzGFpziI86vni6N1SD0'),
('telegram_admin_id', '5318214551'),
('telegram_channel_id', '-1002407669159');