<?php
$songs = json_decode(file_get_contents('songs_db.json'), true);
echo "Checking songs_db.json structure:\n\n";
echo "Total songs: " . count($songs) . "\n\n";

if (count($songs) > 0) {
    $sample = $songs[0];
    echo "First song structure:\n";
    echo "Keys: " . implode(', ', array_keys($sample)) . "\n\n";
    
    echo "Sample song:\n";
    foreach ($sample as $key => $value) {
        echo $key . ": " . $value . "\n";
    }
    
    echo "\n\nChecking first 5 songs for URLs:\n";
    for ($i = 0; $i < 5 && $i < count($songs); $i++) {
        echo ($i+1) . ". " . $songs[$i]['title'] . " - URL: " . ($songs[$i]['url'] ?? 'EMPTY') . "\n";
    }
}
?>
