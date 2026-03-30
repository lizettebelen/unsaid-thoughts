<?php
// Test different queries
$queries = ['queen', 'beatles', 'taylor'];

foreach ($queries as $query) {
    $_GET['q'] = $query;
    ob_start();
    include 'search_songs.php';
    $result = ob_get_clean();
    $data = json_decode($result, true);
    echo "Search for '$query': " . count($data['results']) . " results" . PHP_EOL;
    if (count($data['results']) > 0) {
        foreach (array_slice($data['results'], 0, 3) as $song) {
            echo "  - " . $song['title'] . " by " . $song['artist'] . PHP_EOL;
        }
    }
}
?>
