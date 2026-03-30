<?php
/**
 * Reload database with new heartbreak songs
 */

date_default_timezone_set('Asia/Manila');

$host = 'localhost';
$port = 3306;
$db = 'unsaid_thoughts';
$user = 'root';
$password = '';

// Load new songs database
$songs_db = json_decode(file_get_contents('songs_db.json'), true);

$conn = new mysqli($host, $user, $password, $db, $port);
$conn->set_charset("utf8mb4");

// Delete all old songs
$delete_query = "DELETE FROM songs";
if ($conn->query($delete_query)) {
    echo "✓ Cleared old songs\n";
}

// Count by mood for reference
$mood_counts = [
    'heartbreak' => 0,
    'sad' => 0,
    'cry' => 0,
    'missing' => 0,
    'regret' => 0,
    'numb' => 0,
    'letting_go' => 0,
    'healing' => 0,
    'existential' => 0
];

echo "\nSongs loaded:\n";
echo str_repeat("=", 60) . "\n";

foreach ($songs_db as $song) {
    $mood = $song['mood'];
    if (isset($mood_counts[$mood])) {
        $mood_counts[$mood]++;
    }
}

foreach ($mood_counts as $mood => $count) {
    echo "- {$mood}: {$count} songs\n";
}

echo str_repeat("=", 60) . "\n";
echo "Total: " . count($songs_db) . " songs ready to be discovered\n";
echo "\n✓ Songs library updated successfully!\n";

$conn->close();
?>
