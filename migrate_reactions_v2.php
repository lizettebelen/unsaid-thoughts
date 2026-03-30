<?php
/**
 * Database Migration Script - Update 2
 * Changes reaction system from "multiple per post" to "one per post"
 * Run this once after code deployment
 */

$conn = new mysqli('localhost', 'root', '', 'unsaid_thoughts');
if ($conn->connect_error) {
    die('❌ Database connection failed: ' . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

echo "Starting database migration (Update 2)...\n\n";

try {
    // Check current constraint
    $constraints = $conn->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'reactions' AND COLUMN_NAME = 'user_id'");
    
    echo "Updating reactions table to enforce one reaction per user per post...\n";
    
    // Since we need to change the UNIQUE constraint, we must recreate the table
    // 1. Backup current data
    $conn->query("CREATE TABLE IF NOT EXISTS reactions_backup_v2 LIKE reactions");
    $conn->query("INSERT INTO reactions_backup_v2 SELECT * FROM reactions");
    echo "✅ Backed up current reactions\n\n";
    
    // 2. Drop old table
    $conn->query("DROP TABLE IF EXISTS reactions");
    echo "✅ Removed old reactions table\n";
    
    // 3. Create new table with correct constraint
    $create_reactions_sql = "CREATE TABLE IF NOT EXISTS reactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        thought_id INT NOT NULL,
        user_id VARCHAR(50) NOT NULL,
        type ENUM('heart', 'hug', 'hurt', 'moon') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_post (thought_id, user_id),
        FOREIGN KEY (thought_id) REFERENCES thoughts(id) ON DELETE CASCADE,
        INDEX idx_thought (thought_id)
    )";
    
    $conn->query($create_reactions_sql);
    echo "✅ Created new reactions table\n";
    
    // 4. Migrate data - keep latest reaction per user per post
    $backup_data = $conn->query("SELECT * FROM reactions_backup_v2 ORDER BY created_at DESC");
    $processed = [];
    $migrated_count = 0;
    
    while ($row = $backup_data->fetch_assoc()) {
        $key = $row['thought_id'] . '_' . $row['user_id'];
        
        // Keep only the latest reaction per user per post
        if (!isset($processed[$key])) {
            $processed[$key] = true;
            $insert = $conn->prepare("INSERT INTO reactions (thought_id, user_id, type, created_at) VALUES (?, ?, ?, ?)");
            $insert->bind_param("isss", $row['thought_id'], $row['user_id'], $row['type'], $row['created_at']);
            if ($insert->execute()) {
                $migrated_count++;
            }
        }
    }
    
    echo "✅ Migrated " . $migrated_count . " reactions (keeping latest per user per post)\n\n";
    
    echo "✅ Database migration completed successfully!\n";
    echo "   - Changed from: multiple reactions per user per post\n";
    echo "   - Changed to: one reaction per user per post\n";
    echo "   - Constraint changed: (thought_id, user_id, type) → (thought_id, user_id)\n";
    echo "\nUsers can now:\n";
    echo "   ❤️  React with one emoji/type per post\n";
    echo "   🔄 Switch to a different emoji (replaces previous)\n";
    echo "   ❌ Remove their reaction by clicking the same emoji again\n";
    
} catch (Exception $e) {
    echo "❌ Migration error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
