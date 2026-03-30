<?php
// Quick test of search functionality
$json = json_decode(file_get_contents('songs_db.json'), true);
$tests = [
    'love' => 0,
    'heartbreak' => 0,
    'broken' => 0,
    'kilig' => 0,
    'hate' => 0,
    'moira' => 0,
    'juan karlos' => 0,
    'ebe dancel' => 0
];

foreach ($json as $song) {
    foreach ($tests as $query => &$count) {
        if (stripos($song['title'], $query) !== false || 
            stripos($song['artist'], $query) !== false) {
            $count++;
        }
    }
}

echo "=== NEW SONGS SEARCH TEST ===\n\n";
echo "LOVE/ROMANCE SONGS:\n";
echo "  love: " . $tests['love'] . " songs\n";
echo "  heartbreak: " . $tests['heartbreak'] . " songs\n";
echo "  broken: " . $tests['broken'] . " songs\n";
echo "  moira: " . $tests['moira'] . " songs (OPM artist)\n";
echo "  juan karlos: " . $tests['juan karlos'] . " songs (OPM kilig)\n";
echo "  ebe dancel: " . $tests['ebe dancel'] . " songs (OPM romantic)\n\n";

echo "OTHER EMOTIONS:\n";
echo "  hate: " . $tests['hate'] . " songs\n\n";

echo "=== LOVE SONGS EXAMPLES ===\n";
$count = 0;
foreach ($json as $song) {
    if ($count < 8 && (stripos($song['title'], 'love') !== false || 
        (stripos($song['artist'], 'juan karlos') !== false && $count < 5))) {
        echo "  - " . $song['title'] . " / " . $song['artist'] . "\n";
        $count++;
    }
}

echo "\n=== HEARTBREAK SONGS EXAMPLES ===\n";
$count = 0;
foreach ($json as $song) {
    if ($count < 5 && (stripos($song['title'], 'broken') !== false || 
        stripos($song['artist'], 'olivia rodrigo') !== false)) {
        echo "  - " . $song['title'] . " / " . $song['artist'] . "\n";
        $count++;
    }
}
?>
