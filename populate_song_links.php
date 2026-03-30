<?php
/**
 * Populate sample audio URLs for songs
 * Uses demo URLs that work with HTML5 audio
 */

$conn = new mysqli('localhost', 'root', '', 'unsaid_thoughts');
if ($conn->connect_error) {
    die('❌ Connection failed: ' . $conn->connect_error);
}

echo "🎵 Populating demo audio URLs for songs...\n";

// Sample audio URLs that work with HTML5 audio tags
// These are free/public domain audio samples
$sample_urls = [
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

// Load songs database
$songs_db = json_decode(file_get_contents('songs_db.json'), true);

if (!$songs_db) {
    die('❌ Failed to load songs_db.json');
}

// Update database with cycling sample URLs
$update_count = 0;
$url_index = 0;

foreach ($songs_db as $song) {
    $title = $song['title'];
    $artist = $song['artist'];
    $url = $sample_urls[$url_index % count($sample_urls)];
    
    $stmt = $conn->prepare("UPDATE songs SET link = ? WHERE title = ? AND artist = ?");
    if (!$stmt) {
        echo "❌ Prepare failed: " . $conn->error . "\n";
        continue;
    }
    
    $stmt->bind_param("sss", $url, $title, $artist);
    
    if ($stmt->execute()) {
        $update_count += $stmt->affected_rows;
    }
    
    $stmt->close();
    $url_index++;
}

echo "✅ Updated " . $update_count . " song records with demo audio URLs\n";
echo "📊 Using " . count($sample_urls) . " rotating sample audio files\n";
echo "\n✨ Songs should now play! Test on the explore page.\n";

// Also update the songs_db.json for future song searches
$updated_db = [];
$url_index = 0;

foreach ($songs_db as $song) {
    $song['url'] = $sample_urls[$url_index % count($sample_urls)];
    $updated_db[] = $song;
    $url_index++;
}

file_put_contents('songs_db.json', json_encode($updated_db, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "📝 Updated songs_db.json with audio URLs\n";

$conn->close();
?>
