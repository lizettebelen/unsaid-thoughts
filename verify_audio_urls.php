<?php
$conn = new mysqli('localhost', 'root', '', 'unsaid_thoughts');
if ($conn->connect_error) {
    die('Connection failed');
}

$result = $conn->query('SELECT title, artist, link FROM songs LIMIT 5');
echo "✅ SONG DATABASE VERIFICATION:\n";
echo str_repeat("=", 60) . "\n\n";

while($row = $result->fetch_assoc()) {
    echo '✓ ' . $row['title'] . ' by ' . $row['artist'] . "\n";
    if ($row['link']) {
        echo '  🎵 URL: ' . substr($row['link'], 0, 50) . "...\n";
    } else {
        echo '  ❌ URL: NULL (no audio)\n';
    }
    echo "\n";
}

// Check total songs with URLs
$count = $conn->query('SELECT COUNT(*) as total FROM songs WHERE link IS NOT NULL AND link != ""')->fetch_assoc();
echo str_repeat("=", 60) . "\n";
echo "📊 Songs with audio URLs: " . $count['total'] . "\n";

$conn->close();
?>
