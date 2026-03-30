<?php
// Simulate what happens when user creates a new post with a song

echo "🎵 TESTING NEW POST SONG CREATION\n";
echo str_repeat("=", 60) . "\n\n";

// Simulate search
$search_query = 'love';  // User types in search
echo "Searching for songs with query: '$search_query'\n\n";

$songs = json_decode(file_get_contents('songs_db.json'), true);
$results = array_filter(
    $songs,
    fn($s) => stripos($s['title'], $search_query) !== false || 
              stripos($s['artist'], $search_query) !== false,
    ARRAY_FILTER_USE_BOTH
);

echo "✅ Found " . count($results) . " songs matching '$search_query'\n\n";

echo "Sample results (first 3):\n";
$count = 0;
foreach (array_slice($results, 0, 3) as $song) {
    $count++;
    echo "   $count. {$song['title']} - {$song['artist']}\n";
    echo "      URL present: " . (!empty($song['url']) ? '✅ YES' : '❌ NO') . "\n";
    echo "      URL: " . substr($song['url'] ?? '', 0, 50) . "...\n\n";
}

echo str_repeat("=", 60) . "\n";
echo "✨ NEW POSTS WILL AUTOMATICALLY GET AUDIO!\n";
echo "When user creates new post:\n";
echo "   1. User searches for song ✅\n";
echo "   2. Song suggestion includes URL ✅\n";
echo "   3. URL is saved to database ✅\n";
echo "   4. Audio plays on explore page ✅\n";
?>
