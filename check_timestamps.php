<?php
// Set timezone for consistent timestamp handling
date_default_timezone_set('Asia/Manila');

// Check database timestamps
$host = 'localhost';
$port = 3306;
$db = 'unsaid_thoughts';
$user = 'root';
$password = '';

$conn = new mysqli($host, $user, $password, $db, $port);
$conn->set_charset("utf8mb4");

$query = "SELECT id, content, nickname, created_at FROM thoughts ORDER BY created_at DESC LIMIT 5";
$result = $conn->query($query);

echo "Current Server Time: " . date('Y-m-d H:i:s') . "\n";
echo "Timezone: " . date_default_timezone_get() . "\n\n";

echo "Database timestamps:\n";
while ($row = $result->fetch_assoc()) {
    $created = new DateTime($row['created_at']);
    $now = new DateTime();
    $diff = $now->diff($created);
    
    echo "ID: {$row['id']} | Nickname: {$row['nickname']}\n";
    echo "  Content: " . substr($row['content'], 0, 50) . "\n";
    echo "  Created: {$row['created_at']}\n";
    echo "  Days: {$diff->days} | Hours: {$diff->h} | Minutes: {$diff->i}\n";
    echo "  Display: ";
    
    if ($diff->days == 0 && $diff->h == 0 && $diff->i < 1) {
        echo "now";
    } elseif ($diff->days == 0 && $diff->h == 0) {
        echo $diff->i . 'm ago';
    } elseif ($diff->days == 0) {
        echo $diff->h . 'h ago';
    } elseif ($diff->days < 7) {
        echo $diff->days . 'd ago';
    } else {
        echo $created->format('M d');
    }
    echo "\n\n";
}

$conn->close();
?>
