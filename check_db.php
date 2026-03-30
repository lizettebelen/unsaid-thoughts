<?php
$conn = new mysqli('localhost', 'root', '', 'unsaid_thoughts');
$result = $conn->query('SELECT COUNT(*) as count FROM thoughts');
$row = $result->fetch_assoc();
echo 'Total thoughts: ' . $row['count'] . "\n";

$result = $conn->query('SELECT COUNT(*) as count FROM songs');
$row = $result->fetch_assoc();
echo 'Total songs: ' . $row['count'] . "\n";

$result = $conn->query('SELECT COUNT(*) as count FROM reactions');
$row = $result->fetch_assoc();
echo 'Total reactions: ' . $row['count'] . "\n";

// Show sample thoughts
echo "\nSample thoughts:\n";
$result = $conn->query('SELECT id, content, mood FROM thoughts LIMIT 3');
while ($row = $result->fetch_assoc()) {
    echo "  [ID " . $row['id'] . "] " . substr($row['content'], 0, 50) . "... (" . $row['mood'] . ")\n";
}

$conn->close();
?>
