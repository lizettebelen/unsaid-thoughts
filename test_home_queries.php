<?php
/**
 * Test home.php database queries
 */

require_once 'config_session.php';

$conn = new mysqli('localhost', 'root', '', 'unsaid_thoughts');
if ($conn->connect_error) {
    die('❌ Database connection failed: ' . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

echo "Testing home.php queries...\n\n";

try {
    // Test 1: Fetch thoughts
    echo "1. Fetching thoughts...\n";
    $query = "
        SELECT 
            t.id,
            t.content,
            t.mood,
            t.nickname,
            t.created_at,
            s.id as song_id,
            s.title as song_title,
            s.artist as song_artist,
            s.link as song_link
        FROM thoughts t
        LEFT JOIN songs s ON t.id = s.thought_id
        ORDER BY t.created_at DESC
        LIMIT 5
    ";
    
    $result = $conn->query($query);
    if (!$result) {
        die('❌ Query failed: ' . $conn->error);
    }
    
    $thoughts = [];
    while ($row = $result->fetch_assoc()) {
        $thought_id = $row['id'];
        
        if (!isset($thoughts[$thought_id])) {
            $thoughts[$thought_id] = [
                'id' => $row['id'],
                'content' => substr($row['content'], 0, 50) . '...',
                'song' => null,
                'reactions' => ['heart' => 0, 'hug' => 0, 'hurt' => 0, 'moon' => 0]
            ];
        }
        
        if ($row['song_id'] !== null) {
            $thoughts[$thought_id]['song'] = [
                'title' => $row['song_title'],
                'artist' => $row['song_artist']
            ];
        }
    }
    
    echo "   ✅ Found " . count($thoughts) . " thoughts\n\n";
    
    // Test 2: Fetch reactions for each thought
    echo "2. Fetching reaction counts...\n";
    foreach ($thoughts as $thought_id => $thought) {
        $reactions_query = "SELECT type, COUNT(*) as count FROM reactions WHERE thought_id = ? GROUP BY type";
        $reactions_stmt = $conn->prepare($reactions_query);
        $reactions_stmt->bind_param("i", $thought_id);
        $reactions_stmt->execute();
        $reactions_result = $reactions_stmt->get_result();
        $count = 0;
        while ($reaction_row = $reactions_result->fetch_assoc()) {
            $thoughts[$thought_id]['reactions'][$reaction_row['type']] = (int)$reaction_row['count'];
            $count++;
        }
        echo "   ✅ Thought #" . $thought_id . ": " . $count . " reaction types\n";
    }
    
    echo "\n3. Sample thought data:\n";
    $sample = array_values($thoughts)[0];
    echo "   Content: " . $sample['content'] . "\n";
    if ($sample['song']) {
        echo "   Song: " . $sample['song']['title'] . " by " . $sample['song']['artist'] . "\n";
    }
    echo "   Reactions: ";
    echo "❤️ " . $sample['reactions']['heart'] . " ";
    echo "🤗 " . $sample['reactions']['hug'] . " ";
    echo "💔 " . $sample['reactions']['hurt'] . " ";
    echo "🌙 " . $sample['reactions']['moon'] . "\n";
    
    echo "\n✅ All tests passed! home.php queries working correctly.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
