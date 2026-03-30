<?php
$json = json_decode(file_get_contents('songs_db.json'), true);
echo "✓ Database Updated!" . PHP_EOL;
echo "Total songs: " . count($json) . PHP_EOL;
echo "" . PHP_EOL;

// Test searches for the requested artists
$testArtists = [
    'Adie',
    'Arthur Nery',
    'James Arthur',
    'David Kushner',
    'Halsey',
    'Eraserheads',
    'Parokya ni Edgar',
    'Kamikazee',
    'Silent Sanctuary',
    'Up Dharma Down',
    'Apo Hiking Society',
    'Moonstar88',
    'Bamboo',
    'Ben&Ben'
];

echo "OPM & International Artists Search Results:" . PHP_EOL;
echo str_repeat("=", 50) . PHP_EOL;

foreach ($testArtists as $artist) {
    $results = array_filter($json, function($song) use ($artist) {
        return stripos($song['artist'], $artist) !== false;
    });
    
    $count = count($results);
    echo "" . $artist . ": " . $count . " songs" . PHP_EOL;
    
    foreach (array_slice($results, 0, 2) as $song) {
        echo "   ✓ " . $song['title'] . PHP_EOL;
    }
    
    if ($count > 2) {
        echo "   ... and " . ($count - 2) . " more" . PHP_EOL;
    }
    echo "" . PHP_EOL;
}
?>
