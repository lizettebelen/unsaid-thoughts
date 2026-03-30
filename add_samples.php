<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'unsaid_thoughts');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Sample thoughts data
$sample_thoughts = [
    [
        'content' => "I finally stopped looking for your car in every parking lot. I think I'm ready to move on.",
        'mood' => 'Healing',
        'nickname' => 'Anonymous',
        'song' => ['All Too Well', 'Taylor Swift']
    ],
    [
        'content' => "You were my favorite hello and my hardest goodbye.",
        'mood' => 'Hurt',
        'nickname' => 'Wanderer',
        'song' => ['Someone You Loved', 'Lewis Capaldi']
    ],
    [
        'content' => "Late nights hit different when you're alone with your thoughts. 3 AM never feels the same.",
        'mood' => 'Late Night',
        'nickname' => 'Night Owl',
        'song' => ['Stay', 'The Kid LAROI']
    ],
    [
        'content' => "First time seeing you smile at someone else. First time knowing it wasn't me anymore.",
        'mood' => 'Hurt',
        'nickname' => 'Phoenix',
        'song' => ['Good 4 U', 'Olivia Rodrigo']
    ],
    [
        'content' => "I still remember the way you laughed at my terrible jokes. You made ordinary moments feel magical.",
        'mood' => 'Love',
        'nickname' => 'Dreamer',
        'song' => ['Drivers License', 'Olivia Rodrigo']
    ],
    [
        'content' => "Sometimes the most passionate love stories end the saddest. We tried so hard but we just weren't meant to be.",
        'mood' => 'Passion',
        'nickname' => 'Rebel',
        'song' => ['All of Me', 'John Legend']
    ],
    [
        'content' => "Found your playlist from two years ago. Every song made me feel like I'm drowning in memories.",
        'mood' => 'Late Night',
        'nickname' => 'Echo',
        'song' => ['The Night We Met', 'Lord Huron']
    ],
    [
        'content' => "You taught me that love doesn't always have a happy ending, but it doesn't mean it wasn't real.",
        'mood' => 'Healing',
        'nickname' => 'Sage',
        'song' => ['Let Her Go', 'Passenger']
    ],
    [
        'content' => "Why do we keep loving people who hurt us? Is it because leaving means admitting we chose wrong?",
        'mood' => 'Hurt',
        'nickname' => 'Seeker',
        'song' => ['Hurt', 'Johnny Cash']
    ],
    [
        'content' => "I'm in love with the idea of you, not the reality. But the dream felt too good to let go.",
        'mood' => 'Love',
        'nickname' => 'Romantic',
        'song' => ['Thinking Out Loud', 'Ed Sheeran']
    ]
];

foreach ($sample_thoughts as $thought) {
    // Insert thought
    $content = $conn->real_escape_string($thought['content']);
    $mood = $conn->real_escape_string($thought['mood']);
    $nickname = $conn->real_escape_string($thought['nickname']);
    
    $sql = "INSERT INTO thoughts (content, mood, nickname) VALUES ('$content', '$mood', '$nickname')";
    
    if ($conn->query($sql)) {
        $thought_id = $conn->insert_id;
        
        // Insert song
        $song_title = $conn->real_escape_string($thought['song'][0]);
        $song_artist = $conn->real_escape_string($thought['song'][1]);
        
        $sql_song = "INSERT INTO songs (thought_id, title, artist) VALUES ($thought_id, '$song_title', '$song_artist')";
        $conn->query($sql_song);
        
        // Initialize reactions
        foreach (['heart', 'hug', 'hurt', 'moon'] as $type) {
            $count = rand(5, 50);
            $sql_reaction = "INSERT INTO reactions (thought_id, type, count) VALUES ($thought_id, '$type', $count)";
            $conn->query($sql_reaction);
        }
        
        echo "✅ Added thought ID: $thought_id\n";
    } else {
        echo "❌ Error: " . $conn->error . "\n";
    }
}

echo "\n✨ Sample data added successfully!\n";
$conn->close();
?>
