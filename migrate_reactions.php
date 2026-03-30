<?php
/**
 * Database Migration Script
 * Updates the database schema to support per-user reactions
 * Run this once after deploying the new code
 */

$conn = new mysqli('localhost', 'root', '', 'unsaid_thoughts');
if ($conn->connect_error) {
    die('❌ Database connection failed: ' . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

echo "Starting database migration...\n\n";

try {
    // 1. Add user_id column to thoughts table if it doesn't exist
    $check_user_id = $conn->query("SHOW COLUMNS FROM thoughts LIKE 'user_id'");
    if ($check_user_id->num_rows == 0) {
        echo "Adding user_id column to thoughts table...\n";
        $conn->query("ALTER TABLE thoughts ADD COLUMN user_id VARCHAR(50) AFTER id");
        $conn->query("CREATE INDEX idx_user_id ON thoughts(user_id)");
        echo "✅ Added user_id column to thoughts table\n\n";
    } else {
        echo "⚠️ user_id column already exists in thoughts table\n\n";
    }
    
    // 2. Backup old reactions data (if needed)
    $check_count = $conn->query("SHOW COLUMNS FROM reactions LIKE 'count'");
    if ($check_count->num_rows > 0) {
        echo "Migrating reaction data...\n";
        
        // Create a backup table for old data
        $conn->query("CREATE TABLE IF NOT EXISTS reactions_backup LIKE reactions");
        $conn->query("INSERT INTO reactions_backup SELECT * FROM reactions");
        echo "✅ Created backup of reactions table\n";
        
        // Now we need to expand reactions table to include user_id
        // First, drop the old reactions table and create new one
        $conn->query("DROP TABLE reactions");
        
        // 3. Create new reactions table with user tracking
        $create_reactions_sql = "CREATE TABLE IF NOT EXISTS reactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            thought_id INT NOT NULL,
            user_id VARCHAR(50) NOT NULL,
            type ENUM('heart', 'hug', 'hurt', 'moon') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_reaction (thought_id, user_id, type),
            FOREIGN KEY (thought_id) REFERENCES thoughts(id) ON DELETE CASCADE,
            INDEX idx_thought_type (thought_id, type)
        )";
        
        $conn->query($create_reactions_sql);
        echo "✅ Created new reactions table with user_id support\n\n";
    } else {
        echo "⚠️ Reactions table already has new structure\n\n";
    }
    
    echo "✅ Database migration completed successfully!\n";
    echo "\nNote: Old reaction counts have been backed up in 'reactions_backup' table.\n";
    echo "Users can now react once per type per post.\n";
    
} catch (Exception $e) {
    echo "❌ Migration error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
