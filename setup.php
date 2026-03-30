<?php
/**
 * Database Setup Script
 * Visit this file to create the database and tables
 */

// Database credentials
$host = 'localhost';
$user = 'root';
$password = '';
$db_name = 'unsaid_thoughts';

// First, connect without selecting a database to create it
$conn = new mysqli($host, $user, $password);

if ($conn->connect_error) {
    die('❌ <strong>MySQL Connection Failed!</strong><br>' . 
        'Error: ' . $conn->connect_error . '<br><br>' .
        '✅ <strong>Solution:</strong> Start MySQL in XAMPP Control Panel');
}

$conn->set_charset("utf8mb4");

// SQL commands to create database and tables
$sql_commands = [
    // Create database
    "CREATE DATABASE IF NOT EXISTS `{$db_name}`",
    
    // Use database
    "USE `{$db_name}`",
    
    // Thoughts table
    "CREATE TABLE IF NOT EXISTS `thoughts` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `content` TEXT NOT NULL,
        `mood` VARCHAR(50),
        `nickname` VARCHAR(100),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_created_at` (`created_at`)
    )",
    
    // Songs table
    "CREATE TABLE IF NOT EXISTS `songs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `thought_id` INT NOT NULL,
        `title` VARCHAR(255),
        `artist` VARCHAR(255),
        `link` VARCHAR(500),
        FOREIGN KEY (`thought_id`) REFERENCES `thoughts`(`id`) ON DELETE CASCADE
    )",
    
    // Reactions table
    "CREATE TABLE IF NOT EXISTS `reactions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `thought_id` INT NOT NULL,
        `type` ENUM('heart', 'hug', 'hurt', 'moon') NOT NULL,
        `count` INT DEFAULT 0,
        UNIQUE KEY `unique_reaction` (`thought_id`, `type`),
        FOREIGN KEY (`thought_id`) REFERENCES `thoughts`(`id`) ON DELETE CASCADE
    )"
];

$success = true;
$executed = [];
$errors = [];

// Execute each SQL command
foreach ($sql_commands as $sql) {
    if (empty(trim($sql))) continue;
    
    if ($conn->query($sql)) {
        $executed[] = $sql;
    } else {
        $success = false;
        $errors[] = $sql . " — Error: " . $conn->error;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsaid Thoughts - Database Setup</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #FFF9FC 0%, #FFF5F8 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .container {
            max-width: 600px;
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 4px 30px rgba(255, 105, 180, 0.15);
            border: 1px solid #FFE5F0;
        }
        
        h1 {
            color: #FF69B4;
            margin-bottom: 1rem;
            font-size: 1.8rem;
        }
        
        .status {
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .status.success {
            background-color: #C8E6C9;
            color: #2E7D32;
            border: 1px solid #4CAF50;
        }
        
        .status.error {
            background-color: #FFCDD2;
            color: #C62828;
            border: 1px solid #F44336;
        }
        
        .status strong {
            display: block;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        
        .sql-log {
            background-color: #f5f5f5;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
            max-height: 300px;
            overflow-y: auto;
            font-size: 0.9rem;
            font-family: 'Courier New', monospace;
        }
        
        .sql-log-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid #ddd;
        }
        
        .sql-log-item:last-child {
            border-bottom: none;
        }
        
        .success-item::before {
            content: "✅ ";
            color: #4CAF50;
            font-weight: bold;
        }
        
        .error-item::before {
            content: "❌ ";
            color: #F44336;
            font-weight: bold;
        }
        
        .button {
            display: inline-block;
            margin-top: 1.5rem;
            padding: 0.9rem 1.8rem;
            background: linear-gradient(135deg, #FFB6D9 0%, #FF91C5 100%);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 105, 180, 0.25);
        }
        
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(255, 105, 180, 0.35);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🤫 Unsaid Thoughts - Database Setup</h1>
        
        <?php if ($success && !empty($executed)): ?>
            <div class="status success">
                <strong>✨ Database Setup Complete!</strong>
                All tables have been created successfully.
            </div>
            <p style="text-align: center; margin-top: 1.5rem;">
                <a href="home.php" class="button">Go to Home</a>
            </p>
        <?php else: ?>
            <div class="status error">
                <strong>❌ Setup Failed!</strong>
                <?php if (!empty($errors)): ?>
                    One or more errors occurred during database setup.
                <?php else: ?>
                    Could not connect to MySQL. Make sure MySQL is running in XAMPP.
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($executed) || !empty($errors)): ?>
            <div class="sql-log">
                <strong style="display: block; margin-bottom: 0.8rem;">SQL Execution Log:</strong>
                <?php foreach ($executed as $sql): ?>
                    <div class="sql-log-item success-item">
                        <?php echo substr($sql, 0, 70) . (strlen($sql) > 70 ? '...' : ''); ?>
                    </div>
                <?php endforeach; ?>
                <?php foreach ($errors as $error): ?>
                    <div class="sql-log-item error-item">
                        <?php echo $error; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
