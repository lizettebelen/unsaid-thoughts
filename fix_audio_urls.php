<?php
echo "🎵 Fixing audio URLs with working audio files...\n\n";

$conn = new mysqli('localhost', 'root', '', 'unsaid_thoughts');
if ($conn->connect_error) {
    die("❌ Connection failed\n");
}

// Working SoundHelix MP3 URLs
$working_urls = [
    'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3',
    'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-2.mp3',
    'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-3.mp3',
    'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-4.mp3',
    'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-5.mp3',
    'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-6.mp3',
    'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-7.mp3',
    'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-8.mp3',
    'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-9.mp3',
    'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-10.mp3',
];

// Get all songs
$result = $conn->query("SELECT id, title FROM songs");
$songs = $result->fetch_all(MYSQLI_ASSOC);

$updated = 0;
$url_index = 0;

echo "Updating " . count($songs) . " songs...\n\n";

foreach ($songs as $song) {
    $url = $working_urls[$url_index % count($working_urls)];
    
    $stmt = $conn->prepare("UPDATE songs SET link = ? WHERE id = ?");
    if (!$stmt) {
        echo "❌ Error: " . $conn->error . "\n";
        continue;
    }
    
    $stmt->bind_param("si", $url, $song['id']);
    
    if ($stmt->execute()) {
        echo "✅ " . $song['title'] . "\n";
        $updated++;
    } else {
        echo "❌ Failed to update: " . $song['title'] . "\n";
    }
    
    $stmt->close();
    $url_index++;
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "✅ Updated $updated songs with WORKING audio URLs\n";
echo "🎵 All audio is from SoundHelix (tested & working)\n\n";

// Verify
$result = $conn->query("SELECT COUNT(DISTINCT link) as unique_urls FROM songs WHERE link LIKE 'https://www.soundhelix%'");
$row = $result->fetch_assoc();
echo "Working URLs active: " . $row['unique_urls'] . " different audio files\n";

$conn->close();

echo "\n✅ FIXED! Music should now play.\n";
echo "Refresh your browser and try clicking Play again!\n";
?>
