<?php
$json = json_decode(file_get_contents('songs_db.json'), true);
if (!$json) {
    echo "ERROR: JSON parsing failed!";
    exit(1);
}
echo "Total songs: " . count($json) . "\n\n";

// Count by artist
$opm_artists = ['Adie', 'Arthur Nery', 'Eraserheads', 'Parokya ni Edgar', 'Kamikazee', 
                'Silent Sanctuary', 'Up Dharma Down', 'Apo Hiking Society', 'Moonstar88', 
                'Bamboo', 'Ben&Ben', 'Ebe Dancel', 'UDD', 'Six Cycle Mind', 'Imago'];

echo "OPM ARTISTS:\n";
foreach ($opm_artists as $artist) {
    $count = 0;
    foreach ($json as $song) {
        if ($song['artist'] === $artist) {
            $count++;
        }
    }
    if ($count > 0) {
        echo "  $artist: $count songs\n";
    }
}

// Test search
echo "\n\nSAD SONGS SAMPLE:\n";
$sad_keywords = ['hurt', 'tears', 'breathe', 'cry', 'lonely', 'broken', 'lost'];
$sad_count = 0;
foreach ($json as $song) {
    $lower_title = strtolower($song['title']);
    foreach ($sad_keywords as $keyword) {
        if (strpos($lower_title, $keyword) !== false) {
            echo "  " . $song['title'] . " - " . $song['artist'] . "\n";
            $sad_count++;
            break;
        }
    }
}
echo "\nFound $sad_count sad songs sample\n";

// Taylor Swift count
$ts_count = 0;
foreach ($json as $song) {
    if ($song['artist'] === 'Taylor Swift') {
        $ts_count++;
    }
}
echo "\nTaylor Swift: $ts_count songs\n";

// The Weeknd count
$weeknd_count = 0;
foreach ($json as $song) {
    if ($song['artist'] === 'The Weeknd') {
        $weeknd_count++;
    }
}
echo "The Weeknd: $weeknd_count songs\n";
?>
