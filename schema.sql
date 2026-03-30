-- Unsaid Thoughts Database Schema

-- Create database
CREATE DATABASE IF NOT EXISTS unsaid_thoughts;
USE unsaid_thoughts;

-- Thoughts table
CREATE TABLE IF NOT EXISTS thoughts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50),
    content TEXT NOT NULL,
    mood VARCHAR(50),
    nickname VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at),
    INDEX idx_user_id (user_id)
);

-- Songs table
CREATE TABLE IF NOT EXISTS songs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thought_id INT NOT NULL,
    title VARCHAR(255),
    artist VARCHAR(255),
    link VARCHAR(500),
    FOREIGN KEY (thought_id) REFERENCES thoughts(id) ON DELETE CASCADE
);

-- Reactions table (one reaction per user per post)
CREATE TABLE IF NOT EXISTS reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thought_id INT NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    type ENUM('heart', 'hug', 'hurt', 'moon') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_post (thought_id, user_id),
    FOREIGN KEY (thought_id) REFERENCES thoughts(id) ON DELETE CASCADE,
    INDEX idx_thought (thought_id)
);
