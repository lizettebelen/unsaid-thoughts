<?php
/**
 * Fetch Spotify preview URLs for all songs in database
 * Updates database with actual playable audio links
 */

echo "🎵 Fetching Spotify preview URLs for songs...\n";
echo str_repeat("=", 70) . "\n\n";

$conn = new mysqli('localhost', 'root', '', 'unsaid_thoughts');
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error . "\n");
}

// Get all unique songs from database
$result = $conn->query("SELECT DISTINCT title, artist FROM songs WHERE title IS NOT NULL GROUP BY title, artist");

if (!$result) {
    die("❌ Query failed: " . $conn->error . "\n");
}

$updated_count = 0;
$failed_count = 0;
$total = $result->num_rows;

echo "Processing " . $total . " unique songs...\n\n";

while ($row = $result->fetch_assoc()) {
    $title = $row['title'];
    $artist = $row['artist'];
    
    // Search Spotify for this song
    $search_query = urlencode("$title $artist");
    $spotify_url = "https://api.spotify.com/v1/search?q=$search_query&type=track&limit=1";
    
    try {
        $context = stream_context_create(['http' => ['timeout' => 5]]);
        $response = @file_get_contents($spotify_url, false, $context);
        
        if ($response === false) {
            echo "⏭️  Skipped: $title - $artist (Timeout)\n";
            $failed_count++;
            continue;
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['tracks']['items'][0]['preview_url'])) {
            echo "⏭️  Skipped: $title - $artist (No preview available)\n";
            $failed_count++;
            continue;
        }
        
        $preview_url = $data['tracks']['items'][0]['preview_url'];
        
        if (empty($preview_url)) {
            echo "⏭️  Skipped: $title - $artist (Empty preview URL)\n";
            $failed_count++;
            continue;
        }
        
        // Update database with preview URL
        $stmt = $conn->prepare("UPDATE songs SET link = ? WHERE title = ? AND artist = ?");
        if (!$stmt) {
            echo "❌ Prepare failed for: $title - $artist\n";
            $failed_count++;
            continue;
        }
        
        $stmt->bind_param("sss", $preview_url, $title, $artist);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo "✅ Updated: $title - $artist\n";
                $updated_count++;
            }
        } else {
            echo "❌ Failed to update: $title - $artist\n";
            $failed_count++;
        }
        
        $stmt->close();
        
        // Small delay to avoid rate limiting
        sleep(0.1);
        
    } catch (Exception $e) {
        echo "❌ Error: $title - $artist (" . $e->getMessage() . ")\n";
        $failed_count++;
    }
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "📊 RESULTS:\n";
echo "   ✅ Successfully updated: $updated_count songs\n";
echo "   ⏭️  Skipped/Failed: $failed_count songs\n";
echo "   📈 Total processed: $total songs\n\n";

// Verify the updates
$result = $conn->query("SELECT COUNT(*) as count FROM songs WHERE link LIKE 'https://p.scdn.co%'");
$row = $result->fetch_assoc();
$with_previews = $row['count'];

echo "✨ CURRENT STATUS:\n";
echo "   🎵 Songs with Spotify previews: $with_previews\n";
echo "   🎚️  Songs ready to play: " . $with_previews . "\n\n";

echo "✅ Next: Refresh your browser and click 'Play' on any song!\n";
echo "   Music should now play directly on the site!\n";

$conn->close();
?>
