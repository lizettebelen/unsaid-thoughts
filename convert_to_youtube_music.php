<?php
// Convert all song URLs to YouTube Music format

$songs_file = 'songs_db.json';
$songs = json_decode(file_get_contents($songs_file), true);

foreach ($songs as &$song) {
    // Convert from YouTube results to YouTube Music
    if (strpos($song['url'], 'youtube.com/results') !== false) {
        // Extract search query
        preg_match('/search_query=([^&]+)/', $song['url'], $matches);
        if (isset($matches[1])) {
            $query = $matches[1];
            // Create YouTube Music URL
            $song['url'] = 'https://music.youtube.com/search?q=' . $query;
            $song['type'] = 'youtube_music';
        }
    }
}

// Save updated songs
file_put_contents($songs_file, json_encode($songs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "✓ All songs converted to YouTube Music!\n";
echo "Total songs updated: " . count($songs) . "\n";

// Show sample
echo "\nSample conversions:\n";
for ($i = 0; $i < min(3, count($songs)); $i++) {
    echo ($i+1) . ". " . $songs[$i]['title'] . " - " . $songs[$i]['artist'] . "\n";
    echo "   URL: " . $songs[$i]['url'] . "\n";
}
?>
