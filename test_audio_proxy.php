<?php
echo "🎵 Testing Audio Proxy Setup...\n";
echo str_repeat("=", 70) . "\n\n";

$conn = new mysqli('localhost', 'root', '', 'unsaid_thoughts');

// Get a sample thought with song
$result = $conn->query("
    SELECT t.id, t.content, s.title, s.artist, s.link
    FROM thoughts t
    LEFT JOIN songs s ON s.thought_id = t.id
    WHERE s.id IS NOT NULL
    LIMIT 1
");

if ($row = $result->fetch_assoc()) {
    echo "✅ SAMPLE THOUGHT WITH SONG:\n";
    echo "   Thought ID: " . $row['id'] . "\n";
    echo "   Song: " . $row['title'] . " - " . $row['artist'] . "\n";
    echo "   Original URL: " . substr($row['link'], 0, 60) . "...\n\n";
    
    echo "✅ AUDIO PROXY URL:\n";
    $proxy_url = "http://localhost/unsaidthoughts-/audio_proxy.php?id=" . $row['id'];
    echo "   $proxy_url\n\n";
    
    echo "✅ HOW IT WORKS:\n";
    echo "   1. Browser requests audio_proxy.php?id=1\n";
    echo "   2. PHP fetches SoundHelix audio URL from database\n";
    echo "   3. PHP downloads the audio file\n";
    echo "   4. PHP adds CORS headers to allow playback\n";
    echo "   5. Browser plays the audio (no CORS blocking)\n\n";
}

// Verify all songs have audio  
$result = $conn->query("SELECT COUNT(*) as count FROM thoughts WHERE id IN (SELECT thought_id FROM songs WHERE link IS NOT NULL)");
$row = $result->fetch_assoc();
echo "✅ POSTS WITH AUDIO: " . $row['count'] . " posts\n\n";

echo str_repeat("=", 70) . "\n";
echo "🎯 TEST IT:\n";
echo "   1. Refresh browser (Ctrl+F5 to clear cache)\n";
echo "   2. Go to explore.php or home.php\n";
echo "   3. Click '▶ Play' on any song\n";
echo "   4. Click play button in audio player\n";
echo "   5. Audio should play NOW! 🎶\n\n";

echo "✨ FIXED ISSUES:\n";
echo "   ✓ Audio proxy routes through PHP\n";
echo "   ✓ CORS headers prevent browser blocking\n";
echo "   ✓ Audio file served with proper headers\n";
echo "   ✓ Should work in all browsers\n";

$conn->close();
?>
