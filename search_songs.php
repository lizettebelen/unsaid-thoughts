<?php
/**
 * Search Songs API - YouTube Integration with Local Fallback
 * Searches for songs on YouTube, then falls back to local database
 */

header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');
$get_all = isset($_GET['all']) ? true : false;

if (!$get_all && (empty($query) || strlen($query) < 1)) {
    echo json_encode([
        'success' => false,
        'results' => [],
        'message' => 'Query too short'
    ]);
    exit;
}

$results = [];

try {
    $songs_file = __DIR__ . '/songs_db.json';
    if (file_exists($songs_file)) {
        $songs_db = json_decode(file_get_contents($songs_file), true);
        
        if (is_array($songs_db)) {
            if ($get_all) {
                // Return all songs
                $results = array_map(function($song) {
                    return [
                        'title' => $song['title'],
                        'artist' => $song['artist'],
                        'url' => $song['url'] ?? '',
                        'source' => 'local'
                    ];
                }, $songs_db);
            } else {
                // Search songs
                foreach ($songs_db as $song) {
                    if (stripos($song['title'], $query) !== false || 
                        stripos($song['artist'], $query) !== false) {
                        $results[] = [
                            'title' => $song['title'],
                            'artist' => $song['artist'],
                            'url' => $song['url'] ?? '',
                            'source' => 'local'
                        ];
                    }
                }
            }
        }
    }
} catch (Exception $e) {
    // Silent fallback
}

echo json_encode([
    'success' => true,
    'results' => $results,
    'count' => count($results)
]);
?>
