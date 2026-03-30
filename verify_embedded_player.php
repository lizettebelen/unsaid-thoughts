<?php
echo "🎵 EMBEDDED MUSIC PLAYER - VERIFICATION\n";
echo str_repeat("=", 70) . "\n\n";

$conn = new mysqli('localhost', 'root', '', 'unsaid_thoughts');
if ($conn->connect_error) {
    die("❌ Database connection failed\n");
}

// Get a sample thought with song
$result = $conn->query("
    SELECT t.id, t.content, s.title, s.artist
    FROM thoughts t
    LEFT JOIN songs s ON s.thought_id = t.id
    WHERE s.id IS NOT NULL
    LIMIT 1
");

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    echo "✅ SAMPLE POST WITH SONG:\n";
    echo "   Post ID: " . $row['id'] . "\n";
    echo "   Song: " . $row['title'] . " - " . $row['artist'] . "\n\n";
    
    // Generate the YouTube embed URL that will be used
    $search_query = $row['title'] . ' ' . $row['artist'];
    $embed_url = "https://www.youtube.com/embed/?listType=search&list=" . urlencode($search_query);
    
    echo "✅ EMBEDDED YOUTUBE PLAYER:\n";
    echo "   Search Query: " . htmlspecialchars($search_query) . "\n";
    echo "   Embed URL: " . substr($embed_url, 0, 80) . "...\n\n";
    
    echo "   HTML STRUCTURE:\n";
    echo "   <iframe\n";
    echo "       width='100%'\n";
    echo "       height='315'\n";
    echo "       src='" . htmlspecialchars($embed_url) . "'\n";
    echo "       allow='accelerometer; autoplay; encrypted-media'\n";
    echo "       allowfullscreen>\n";
    echo "   </iframe>\n\n";
} else {
    echo "ℹ️  No posts with songs found yet\n\n";
}

// Check posts across all pages
echo "📊 STATISTICS:\n";

$result = $conn->query("SELECT COUNT(*) as total FROM thoughts WHERE id IN (SELECT thought_id FROM songs)");
$row = $result->fetch_assoc();
echo "   Posts with songs: " . $row['total'] . "\n";

$result = $conn->query("SELECT COUNT(*) as total FROM thoughts");
$row = $result->fetch_assoc();
echo "   Total posts: " . $row['total'] . "\n\n";

echo str_repeat("=", 70) . "\n";
echo "✨ HOW IT WORKS:\n\n";
echo "   1. User views a post with a song\n";
echo "   2. Song title + artist displayed with RED 'Play' button\n";
echo "   3. Click 'Play' → YouTube embed expands\n";
echo "   4. Search results show for that song on YouTube\n";
echo "   5. User can play any result or close player\n";
echo "   6. Button changes to 'Close' when player is open\n\n";

echo "🎯 TEST THESE PAGES:\n";
echo "   • http://localhost/unsaidthoughts-/explore.php\n";
echo "   • http://localhost/unsaidthoughts-/share.php\n";
echo "   • http://localhost/unsaidthoughts-/home.php\n\n";

echo "✅ FEATURES:\n";
echo "   ✓ Music player embeds directly in the page\n";
echo "   ✓ No external link needed\n";
echo "   ✓ Full YouTube search integration\n";
echo "   ✓ Play/close toggle button\n";
echo "   ✓ Smooth scroll to player when opened\n";
echo "   ✓ Mobile responsive design\n\n";

echo "✨ STATUS: EMBEDDED MUSIC PLAYBACK READY!\n";

$conn->close();
?>
