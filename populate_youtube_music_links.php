<?php
/**
 * Populate missing song links from songs_db.json
 */

date_default_timezone_set('Asia/Manila');

$host = 'localhost';
$port = 3306;
$db = 'unsaid_thoughts';
$user = 'root';
$password = '';

// Load songs database
$songs_db = json_decode(file_get_contents('songs_db.json'), true);

// Create lookup by title+artist
$songs_lookup = [];
foreach ($songs_db as $song) {
    $key = strtolower(trim($song['title'] . ' ' . $song['artist']));
    $songs_lookup[$key] = $song['url'];
}

$conn = new mysqli($host, $user, $password, $db, $port);
$conn->set_charset("utf8mb4");

// Find songs with empty or non-YouTube Music links
$query = "
    SELECT s.id, s.title, s.artist, s.link
    FROM songs s
    WHERE s.link IS NULL OR s.link = '' OR s.link NOT LIKE '%music.youtube.com%'
";

$result = $conn->query($query);

$updated = 0;
$not_found = 0;

echo "Updating songs with YouTube Music links...\n\n";

while ($row = $result->fetch_assoc()) {
    $key = strtolower(trim($row['title'] . ' ' . $row['artist']));
    
    if (isset($songs_lookup[$key])) {
        $new_url = $songs_lookup[$key];
        
        $update_query = "UPDATE songs SET link = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $new_url, $row['id']);
        
        if ($update_stmt->execute()) {
            echo "✓ Updated: " . $row['title'] . " - " . $row['artist'] . "\n";
            $updated++;
        }
    } else {
        echo "✗ Not found in DB: " . $row['title'] . " - " . $row['artist'] . "\n";
        $not_found++;
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Summary:\n";
echo "- Updated: " . $updated . " songs\n";
echo "- Not found in songs_db.json: " . $not_found . " songs\n";

$conn->close();
?>
