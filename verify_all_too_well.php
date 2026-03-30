<?php
$json = json_decode(file_get_contents('songs_db.json'), true);
echo "Total songs: " . count($json) . PHP_EOL;

// Search for "all too well"
$query = "all too well";
$results = array_filter($json, function($song) use ($query) {
    return stripos($song['title'], $query) !== false || stripos($song['artist'], $query) !== false;
});

echo "Search for '$query': " . count($results) . " results" . PHP_EOL;
foreach (array_slice($results, 0, 5) as $song) {
    echo "  ✓ " . $song['title'] . " by " . $song['artist'] . PHP_EOL;
}
?>
