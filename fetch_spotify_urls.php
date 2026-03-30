<?php
/**
 * Fetch Spotify preview URLs for all songs
 * Uses Spotify Web API to get playable 30-second previews
 */

echo "🎵 Fetching Spotify preview URLs...\n";
echo str_repeat("=", 70) . "\n\n";

$conn = new mysqli('localhost', 'root', '', 'unsaid_thoughts');
if ($conn->connect_error) {
    die("❌ Connection failed\n");
}

// Get all unique songs
$result = $conn->query("SELECT DISTINCT title, artist FROM songs");
$songs = $result->fetch_all(MYSQLI_ASSOC);

$updated = 0;
$failed = 0;

echo "Processing " . count($songs) . " songs...\n\n";

foreach ($songs as $song) {
    $title = $song['title'];
    $artist = $song['artist'];
    
    // Search Spotify for this song
    $search_query = urlencode("track:$title artist:$artist");
    $spotify_api = "https://api.spotify.com/v1/search?q=$search_query&type=track&limit=1";
    
    // Note: Spotify API requires authentication, but we can try public endpoint
    // Alternative: use a public Spotify search
    
    try {
        // Create context with timeout
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Mozilla/5.0'
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        // Try to fetch from Spotify API
        $response = @file_get_contents($spotify_api, false, $context);
        
        if (!$response) {
            // Fallback: Create Spotify search URL
            $spotify_search_url = "https://open.spotify.com/search/" . urlencode("$title $artist");
            echo "⏭️  $title - $artist (Using search link)\n";
            $failed++;
            continue;
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['tracks']['items'][0]['preview_url'])) {
            echo "⏭️  $title - $artist (No preview available)\n";
            $failed++;
            continue;
        }
        
        $preview_url = $data['tracks']['items'][0]['preview_url'];
        
        if (empty($preview_url)) {
            echo "⏭️  $title - $artist (Empty preview)\n";
            $failed++;
            continue;
        }
        
        // Update database
        $stmt = $conn->prepare("UPDATE songs SET link = ? WHERE title = ? AND artist = ?");
        if ($stmt) {
            $stmt->bind_param("sss", $preview_url, $title, $artist);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                echo "✅ $title - $artist\n";
                $updated++;
            }
            $stmt->close();
        }
        
        sleep(0.1); // Rate limiting
        
    } catch (Exception $e) {
        echo "❌ Error: $title - $artist\n";
        $failed++;
    }
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "Results: Updated $updated, Failed $failed\n";

$conn->close();
?>
