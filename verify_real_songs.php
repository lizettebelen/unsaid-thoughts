<?php
/**
 * Verify all songs in database are existing songs
 */

date_default_timezone_set('Asia/Manila');

$host = 'localhost';
$port = 3306;
$db = 'unsaid_thoughts';
$user = 'root';
$password = '';

// Load songs database for verification
$songs_db = json_decode(file_get_contents('songs_db.json'), true);

// Create lookup
$songs_lookup = [];
foreach ($songs_db as $song) {
    $key = strtolower(trim($song['title'] . ' ' . $song['artist']));
    $songs_lookup[$key] = true;
}

$conn = new mysqli($host, $user, $password, $db, $port);
$conn->set_charset("utf8mb4");

$query = "
    SELECT s.id, s.title, s.artist, s.link, t.nickname
    FROM songs s
    LEFT JOIN thoughts t ON s.thought_id = t.id
    ORDER BY s.id DESC
";

$result = $conn->query($query);

echo "Verifying songs in database...\n";
echo str_repeat("=", 80) . "\n";

$existing = 0;
$not_found = 0;
$songs_to_show = [];

while ($row = $result->fetch_assoc()) {
    $key = strtolower(trim($row['title'] . ' ' . $row['artist']));
    
    if (isset($songs_lookup[$key])) {
        $existing++;
        $status = "✓ EXISTS";
    } else {
        $not_found++;
        $status = "✗ NOT FOUND";
    }
    
    $songs_to_show[] = [
        'title' => $row['title'],
        'artist' => $row['artist'],
        'nickname' => $row['nickname'],
        'status' => $status
    ];
}

// Display results
foreach ($songs_to_show as $song) {
    echo $song['status'] . " | " . $song['title'] . " - " . $song['artist'] . " (by " . ($song['nickname'] ?: 'Anonymous') . ")\n";
}

echo str_repeat("=", 80) . "\n";
echo "Summary:\n";
echo "- Real existing songs: " . $existing . "\n";
echo "- Not in database: " . $not_found . "\n";
echo "- Total songs in database: " . ($existing + $not_found) . "\n";

if ($not_found > 0) {
    echo "\n⚠️ Songs not in songs_db.json need to be added or removed!\n";
}

$conn->close();
?>
