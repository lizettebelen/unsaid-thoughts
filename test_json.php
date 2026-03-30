<?php
$json = json_decode(file_get_contents('songs_db.json'), true);
if ($json === null) {
    echo 'ERROR: JSON decode failed' . PHP_EOL;
    echo 'Last error: ' . json_last_error_msg() . PHP_EOL;
} else {
    echo 'SUCCESS: JSON loaded' . PHP_EOL;
    echo 'Total songs: ' . count($json) . PHP_EOL;
    echo 'First song: ' . $json[0]['title'] . ' by ' . $json[0]['artist'] . PHP_EOL;
    
    $query = 'all';
    $count = 0;
    $results = array();
    foreach ($json as $song) {
        if (stripos($song['title'], $query) !== false || stripos($song['artist'], $query) !== false) {
            $count++;
            $results[] = $song;
        }
    }
    echo 'Search for "' . $query . '": ' . $count . ' songs found' . PHP_EOL;
    foreach (array_slice($results, 0, 5) as $song) {
        echo '  - ' . $song['title'] . ' by ' . $song['artist'] . PHP_EOL;
    }
}
?>
