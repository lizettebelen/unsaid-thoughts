<?php
/**
 * Check songs in database with missing or empty links
 */

date_default_timezone_set('Asia/Manila');

$host = 'localhost';
$port = 3306;
$db = 'unsaid_thoughts';
$user = 'root';
$password = '';

$conn = new mysqli($host, $user, $password, $db, $port);
$conn->set_charset("utf8mb4");

$query = "
    SELECT s.id, s.title, s.artist, s.link, t.nickname, t.created_at
    FROM songs s
    LEFT JOIN thoughts t ON s.thought_id = t.id
    ORDER BY s.id DESC
";

$result = $conn->query($query);

echo "Songs in database:\n";
echo "ID | Title | Artist | LinkStatus\n";
echo str_repeat("-", 100) . "\n";

while ($row = $result->fetch_assoc()) {
    $link_status = empty($row['link']) ? 'EMPTY' : (strpos($row['link'], 'music.youtube.com') !== false ? 'YouTube Music ✓' : 'Other');
    echo $row['id'] . " | " . $row['title'] . " | " . $row['artist'] . " | " . $link_status . "\n";
}

$conn->close();
?>
