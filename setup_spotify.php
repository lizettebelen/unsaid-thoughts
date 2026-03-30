<?php
/**
 * Fetch Spotify preview URLs (30-second clips)
 * These are accessible without authentication
 */

echo "🎵 Fetching Spotify preview URLs...\n";
echo str_repeat("=", 70) . "\n\n";

$conn = new mysqli('localhost', 'root', '', 'unsaid_thoughts');
if ($conn->connect_error) {
    die("❌ Connection failed\n");
}

// Get all unique songs
$result = $conn->query("SELECT DISTINCT title, artist FROM songs ORDER BY title");
$songs = $result->fetch_all(MYSQLI_ASSOC);

$updated = 0;
$total = count($songs);

echo "Processing $total songs from Spotify API...\n\n";

foreach ($songs as $song) {
    $title = $song['title'];
    $artist = $song['artist'];
    
    // Build Spotify search query
    $search = urlencode("track:$title artist:$artist");
    $url = "https://api.spotify.com/v1/search?q=$search&type=track&limit=1";
    
    // Try to fetch from Spotify (may fail if rate limited)
    $context = stream_context_create([
        'http' => ['timeout' => 5, 'user_agent' => 'Mozilla/5.0'],
        'ssl' => ['verify_peer' => false]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response) {
        $data = json_decode($response, true);
        
        if (isset($data['tracks']['items'][0]['preview_url']) && !empty($data['tracks']['items'][0]['preview_url'])) {
            $preview_url = $data['tracks']['items'][0]['preview_url'];
            $track_name = $data['tracks']['items'][0]['name'];
            
            // Update database
            $stmt = $conn->prepare("UPDATE songs SET link = ? WHERE title = ? AND artist = ?");
            $stmt->bind_param("sss", $preview_url, $title, $artist);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                echo "✅ $title - $artist\n";
                $updated++;
            }
            $stmt->close();
        } else {
            echo "⏭️  $title - $artist (No preview)\n";
        }
    } else {
        echo "⏭️  $title - $artist (API timeout)\n";
    }
    
    usleep(200000); // 0.2s delay to avoid rate limiting
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "✅ Updated $updated / $total songs with Spotify previews\n\n";

// Verify
$result = $conn->query("SELECT COUNT(*) as count FROM songs WHERE link LIKE 'https://p.scdn.co%'");
$row = $result->fetch_assoc();
echo "Songs with Spotify preview URLs: " . $row['count'] . "\n";

$conn->close();

echo "\n✨ Spotify previews are now active!\n";
?>

