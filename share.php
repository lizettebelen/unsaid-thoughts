<?php
// Include session config for user tracking
require_once 'config_session.php';

// Database connection
$conn = new mysqli('localhost', 'root', '', 'unsaid_thoughts');
if ($conn->connect_error) {
    die('❌ Database connection failed: ' . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Get current user ID
$current_user_id = getCurrentUserId();

// Handle delete requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $thought_id = (int)$_POST['thought_id'] ?? 0;
    
    if ($thought_id > 0) {
        try {
            // First check if this thought belongs to the current user
            $check_query = "SELECT user_id FROM thoughts WHERE id = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("i", $thought_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $thought = $check_result->fetch_assoc();
            
            // Only allow deletion if it's the user's own thought
            if ($thought && $thought['user_id'] === $current_user_id) {
                // Delete reactions first (foreign key constraint)
                $delete_reactions = "DELETE FROM reactions WHERE thought_id = ?";
                $react_stmt = $conn->prepare($delete_reactions);
                $react_stmt->bind_param("i", $thought_id);
                $react_stmt->execute();
                
                // Delete songs associated with this thought
                $delete_songs = "DELETE FROM songs WHERE thought_id = ?";
                $song_stmt = $conn->prepare($delete_songs);
                $song_stmt->bind_param("i", $thought_id);
                $song_stmt->execute();
                
                // Delete the thought
                $delete_thought = "DELETE FROM thoughts WHERE id = ?";
                $delete_stmt = $conn->prepare($delete_thought);
                $delete_stmt->bind_param("i", $thought_id);
                $delete_stmt->execute();
            }
        } catch (Exception $e) {
            // Silently fail on error
        }
    }
    exit;
}

// Handle reaction updates via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'react') {
    header('Content-Type: application/json');
    
    $thought_id = (int)$_POST['thought_id'] ?? 0;
    $reaction_type = $_POST['reaction_type'] ?? '';
    
    $valid_types = ['heart', 'hug', 'hurt', 'moon'];
    if (!in_array($reaction_type, $valid_types) || $thought_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
        exit;
    }
    
    try {
        $conn->begin_transaction();
        
        // Check if user already has a reaction on this post
        $check_query = "SELECT type FROM reactions WHERE thought_id = ? AND user_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("is", $thought_id, $current_user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $existing = $check_result->fetch_assoc();
        
        if ($existing) {
            $existing_type = $existing['type'];
            
            // If clicking the same reaction - remove it (toggle off)
            if ($existing_type === $reaction_type) {
                $delete_query = "DELETE FROM reactions WHERE thought_id = ? AND user_id = ?";
                $delete_stmt = $conn->prepare($delete_query);
                $delete_stmt->bind_param("is", $thought_id, $current_user_id);
                $delete_stmt->execute();
                $action_type = 'removed';
                $new_type = null;
            } else {
                // Different reaction - UPDATE to new type
                $update_query = "UPDATE reactions SET type = ? WHERE thought_id = ? AND user_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("sis", $reaction_type, $thought_id, $current_user_id);
                $update_stmt->execute();
                $action_type = 'changed';
                $new_type = $reaction_type;
            }
        } else {
            // No existing reaction - INSERT new one
            $insert_query = "INSERT INTO reactions (thought_id, user_id, type) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("iss", $thought_id, $current_user_id, $reaction_type);
            $insert_stmt->execute();
            $action_type = 'added';
            $new_type = $reaction_type;
        }
        
        $conn->commit();
        
        // Get updated counts for all reaction types
        $count_query = "SELECT type, COUNT(*) as count FROM reactions WHERE thought_id = ? GROUP BY type";
        $count_stmt = $conn->prepare($count_query);
        $count_stmt->bind_param("i", $thought_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $counts = ['heart' => 0, 'hug' => 0, 'hurt' => 0, 'moon' => 0];
        while ($row = $count_result->fetch_assoc()) {
            $counts[$row['type']] = (int)$row['count'];
        }
        
        echo json_encode(['success' => true, 'action' => $action_type, 'counts' => $counts, 'current_reaction' => $new_type]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}

// Get all thoughts sorted by reactions
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'recent';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 4;

$query = "SELECT t.id, t.content, t.mood, t.nickname, t.created_at,
          s.title as song_title, s.artist as song_artist, s.link as song_link
          FROM thoughts t
          LEFT JOIN songs s ON t.id = s.thought_id
          WHERE t.user_id = '" . $conn->real_escape_string($current_user_id) . "'";

if ($sort_by === 'loved') {
    $query .= " GROUP BY t.id
               ORDER BY (SELECT COUNT(*) FROM reactions WHERE thought_id = t.id AND type = 'heart') DESC, t.created_at DESC";
} elseif ($sort_by === 'trending') {
    $query .= " GROUP BY t.id
               ORDER BY (SELECT COUNT(*) FROM reactions WHERE thought_id = t.id) DESC, t.created_at DESC";
} else {
    $query .= " ORDER BY t.created_at DESC";
}

// Count total thoughts by current user
$count_result = $conn->query("SELECT COUNT(DISTINCT t.id) as total FROM thoughts t WHERE t.user_id = '" . $conn->real_escape_string($current_user_id) . "'");
$count_row = $count_result->fetch_assoc();
$total_thoughts = $count_row['total'];
$total_pages = ceil($total_thoughts / $per_page);

if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}
if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $per_page;
$query .= " LIMIT " . $per_page . " OFFSET " . $offset;

$result = $conn->query($query);

// Organize thoughts
$thoughts = [];
while ($row = $result->fetch_assoc()) {
    $thought_id = $row['id'];
    if (!isset($thoughts[$thought_id])) {
        $thoughts[$thought_id] = [
            'id' => $row['id'],
            'content' => $row['content'],
            'mood' => $row['mood'],
            'nickname' => $row['nickname'],
            'created_at' => $row['created_at'],
            'song' => null,
            'reactions' => ['heart' => 0, 'hug' => 0, 'hurt' => 0, 'moon' => 0]
        ];
    }
    if ($row['song_title']) {
        $thoughts[$thought_id]['song'] = [
            'title' => $row['song_title'],
            'artist' => $row['song_artist'],
            'link' => $row['song_link']
        ];
    }
}

// Fetch reactions for all thoughts BEFORE closing connection
foreach ($thoughts as $thought_id => $thought) {
    $reactions_query = "SELECT type, COUNT(*) as count FROM reactions WHERE thought_id = ? GROUP BY type";
    $reactions_stmt = $conn->prepare($reactions_query);
    $reactions_stmt->bind_param("i", $thought_id);
    $reactions_stmt->execute();
    $reactions_result = $reactions_stmt->get_result();
    if ($reactions_result) {
        while ($row = $reactions_result->fetch_assoc()) {
            $thoughts[$thought_id]['reactions'][$row['type']] = (int)$row['count'];
        }
    }
}

// Fetch user's reaction for all thoughts (max one per post)
$user_reactions = [];
$user_reactions_query = "SELECT thought_id, type FROM reactions WHERE user_id = ?";
$user_reactions_stmt = $conn->prepare($user_reactions_query);
$user_reactions_stmt->bind_param("s", $current_user_id);
$user_reactions_stmt->execute();
$user_reactions_result = $user_reactions_stmt->get_result();
while ($row = $user_reactions_result->fetch_assoc()) {
    $user_reactions[$row['thought_id']] = $row['type'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Thoughts - Unsaid Thoughts</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Caveat:wght@400;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #FFF9FC 0%, #FFF5F8 100%);
            min-height: 100vh;
            padding-bottom: 80px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        }

        .header {
            display: none;
        }

        .page-title {
            display: none;
        }

        .subtitle {
            display: none;
        }

        /* Sort buttons */
        .sort-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 3rem;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .sort-btn {
            padding: 10px 18px;
            border: 2px solid #FFD1E8;
            background: white;
            border-radius: 24px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            color: #D291BC;
            text-decoration: none;
            display: inline-block;
        }

        .sort-btn:hover {
            transform: translateY(-4px) scale(1.05);
            box-shadow: 0 8px 20px rgba(255, 105, 180, 0.25);
            border-color: #FF69B4;
            color: #FF69B4;
        }

        .sort-btn.active {
            background: linear-gradient(135deg, #FFB6D9 0%, #FF91C5 100%);
            border-color: #FF69B4;
            color: white;
            box-shadow: 0 8px 20px rgba(255, 105, 180, 0.35);
        }

        .thought-card {
            background: white;
            border: 2px solid #FFE5F0;
            border-radius: 16px;
            padding: 12px;
            position: relative;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 105, 180, 0.08);
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
        }

        .thought-card:hover {
            box-shadow: 0 12px 35px rgba(255, 105, 180, 0.2);
            border-color: #FFB6D9;
            transform: translateY(-4px);
        }

        .thought-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2px;
            gap: 8px;
        }

        .thought-author-mood {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .thought-author {
            font-size: 11px;
            font-weight: 600;
            color: #333;
            max-width: 100px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .mood-tag {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            background: #f0f0f0;
            color: #666;
            text-transform: capitalize;
        }

        .mood-tag.Love { background: rgba(255, 107, 157, 0.1); color: #ff6b9d; }
        .mood-tag.Hurt { background: rgba(196, 69, 105, 0.1); color: #c44569; }

        .timestamp {
            font-size: 11px;
            color: #999;
        }

        .thought-content {
            font-size: 14px;
            color: #333;
            margin-bottom: 8px;
            line-height: 1.4;
            font-style: italic;
        }

        .song-info {
            background: #f9f9f9;
            padding: 8px;
            border-radius: 6px;
            margin-bottom: 8px;
            border-left: 3px solid #ff6b9d;
        }

        .song-title {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .song-artist {
            font-size: 12px;
            color: #999;
        }

        /* Reactions */
        .reactions {
            display: flex;
            justify-content: space-around;
            align-items: center;
            gap: 2px;
        }

        .reaction-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2px;
            padding: 4px 6px;
            background: transparent;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 10px;
            color: #999;
            flex: 1;
        }

        .reaction-btn:hover {
            background: #fff5f7;
            transform: scale(1.1);
        }

        .reaction-btn.active-reaction {
            background: #ffe6f0;
            color: #ff6b9d;
        }

        .reaction-emoji {
            font-size: 18px;
        }

        .reaction-count {
            font-weight: 600;
            font-size: 10px;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        /* Pagination */
        .pagination {
            text-align: center;
            margin: 30px 0;
        }

        .flip-btn {
            padding: 12px 32px;
            border: 2px solid #333;
            background: white;
            color: #333;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            margin: 0 8px;
        }

        .flip-btn:hover {
            background: #333;
            color: white;
        }

        .flip-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }



        .nav-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        @media (max-width: 480px) {
            body {
                padding-bottom: 80px;
            }

            .page-title {
                font-size: 28px;
            }

            .sort-buttons {
                gap: 8px;
            }

            .sort-btn {
                padding: 6px 12px;
                font-size: 12px;
            }

            .thought-card {
                padding: 12px;
            }

            .thought-author-mood {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <?php
        $header_title = '✧ My Thoughts';
        $header_subtitle = 'Your unsaid thoughts and how they touched hearts';
        include 'header.php';
    ?>

    <div class="container">
        <!-- Page Title -->
        <h1 class="page-title">✧ Reactions</h1>
        <p class="subtitle">See how others responded to each thought.</p>

        <!-- Sort Buttons -->
        <div class="sort-buttons">
            <a href="share.php?sort=recent" class="sort-btn <?php echo $sort_by === 'recent' ? 'active' : ''; ?>">
                🕐 Recent
            </a>
            <a href="share.php?sort=loved" class="sort-btn <?php echo $sort_by === 'loved' ? 'active' : ''; ?>">
                ❤️ Most Loved
            </a>
            <a href="share.php?sort=trending" class="sort-btn <?php echo $sort_by === 'trending' ? 'active' : ''; ?>">
                🔥 Trending
            </a>
        </div>

        <!-- Thoughts -->
        <div class="thoughts-container">
            <?php if (count($thoughts) > 0): ?>
                <?php foreach ($thoughts as $thought): ?>
                    <div class="thought-card">
                        <div class="thought-header">
                            <div class="thought-author-mood">
                                <span class="mood-tag <?php echo htmlspecialchars($thought['mood']); ?>">
                                    <?php echo htmlspecialchars($thought['mood']); ?>
                                </span>
                                <span class="thought-author"><?php echo htmlspecialchars($thought['nickname']); ?></span>
                            </div>
                            <span class="timestamp"><?php echo randomTimestamp(); ?></span>
                        </div>
                        <button onclick="deleteThought(<?php echo $thought['id']; ?>)" style="padding: 4px 8px; background-color: #FFB6D9; border: 1px solid #FF69B4; border-radius: 6px; color: #FF1493; font-size: 11px; font-weight: 600; cursor: pointer; transition: all 0.2s; align-self: flex-start;">
                            ✕ Delete
                        </button>

                        <p class="thought-content"><?php echo htmlspecialchars($thought['content']); ?></p>

                        <?php if ($thought['song']): ?>
                            <div class="song-info">
                                <div style="display: flex; flex-direction: column; gap: 12px; width: 100%;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div style="flex: 1;">
                                            <div class="song-title">🎵 <?php echo htmlspecialchars($thought['song']['title']); ?></div>
                                            <div class="song-artist"><?php echo htmlspecialchars($thought['song']['artist']); ?></div>
                                        </div>
                                        <button class="btn-play-toggle" onclick="toggleMusicPlayer(this, <?php echo htmlspecialchars(json_encode($thought['song'])); ?>)"
                                                style="padding: 6px 12px; background-color: #ff0000; color: white; border: none; border-radius: 4px; font-size: 12px; font-weight: 500; cursor: pointer;">
                                            ▶ Play
                                        </button>
                                    </div>
                                    <!-- YouTube Music Player -->
                                    <div class="music-player-container" style="display: none; width: calc(100% - 8px); max-width: 500px; margin-left: auto; margin-right: auto; background: linear-gradient(135deg, #FF0000 0%, #ff6b6b 100%); border-radius: 16px; padding: 24px; box-shadow: 0 8px 32px rgba(255,0,0,0.15); position: relative; overflow: hidden;">
                                        <!-- Decorative background elements -->
                                        <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: rgba(255,255,255,0.05); border-radius: 50%; pointer-events: none;"></div>
                                        <div style="position: absolute; bottom: -30px; left: -30px; width: 100px; height: 100px; background: rgba(255,255,255,0.05); border-radius: 50%; pointer-events: none;"></div>
                                        
                                        <div style="position: relative; z-index: 1; display: flex; flex-direction: column; gap: 16px;">
                                            <!-- Header with title and artist -->
                                            <div style="text-align: center; color: white;">
                                                <div style="font-size: 12px; letter-spacing: 1px; opacity: 0.85; text-transform: uppercase; font-weight: 600; margin-bottom: 8px;">🎵 Now Playing</div>
                                                <div style="font-size: 22px; font-weight: 800; margin-bottom: 6px; line-height: 1.2;"><?php echo htmlspecialchars($thought['song']['title']); ?></div>
                                                <div style="font-size: 14px; opacity: 0.9; font-weight: 500;"><?php echo htmlspecialchars($thought['song']['artist']); ?></div>
                                            </div>
                                            
                                            <!-- Main button -->
                                            <a href="https://music.youtube.com/search?q=<?php echo urlencode($thought['song']['title'] . ' ' . $thought['song']['artist']); ?>" 
                                               target="_blank"
                                               style="display: flex; align-items: center; justify-content: center; gap: 10px; padding: 14px 24px; background-color: white; color: #FF0000; text-decoration: none; border-radius: 25px; font-size: 14px; font-weight: 700; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0,0,0,0.1);" onmouseover="this.style.backgroundColor='#f0f0f0'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.15)'; this.style.transform='translateY(-2px)';" onmouseout="this.style.backgroundColor='white'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.1)'; this.style.transform='translateY(0)';">
                                                <span style="font-size: 16px;">▶</span> Play on YouTube Music
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Reactions -->
                        <div class="reactions">
                            <?php 
                            $reactions = $thought['reactions'];
                            $reaction_data = [
                                ['emoji' => '❤️', 'type' => 'heart', 'label' => 'Love'],
                                ['emoji' => '🤗', 'type' => 'hug', 'label' => 'Hug'],
                                ['emoji' => '💔', 'type' => 'hurt', 'label' => 'Hurt'],
                                ['emoji' => '🌙', 'type' => 'moon', 'label' => 'Moon']
                            ];
                            ?>
                            <?php foreach ($reaction_data as $reaction): ?>
                                <?php 
                                $is_user_reaction = isset($user_reactions[$thought['id']]) && $user_reactions[$thought['id']] === $reaction['type'];
                                $btn_class = $is_user_reaction ? 'reaction-btn active-reaction' : 'reaction-btn';
                                ?>
                                <button class="<?php echo $btn_class; ?>" onclick="addReaction(<?php echo $thought['id']; ?>, '<?php echo $reaction['type']; ?>', this)">
                                    <span class="reaction-emoji"><?php echo $reaction['emoji']; ?></span>
                                    <span class="reaction-count" data-count="<?php echo $reactions[$reaction['type']]; ?>">
                                        <?php echo $reactions[$reaction['type']]; ?>
                                    </span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🤐</div>
                    <div>No thoughts yet. Start exploring!</div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_thoughts > 0): ?>
            <div class="pagination">
                <button class="flip-btn" onclick="window.location.href='share.php?sort=<?php echo urlencode($sort_by); ?>&page=<?php echo max(1, $page - 1); ?>'" 
                    <?php echo $page <= 1 ? 'disabled' : ''; ?>>
                    ← Previous
                </button>
                <button class="flip-btn" onclick="window.location.href='share.php?sort=<?php echo urlencode($sort_by); ?>&page=<?php echo min($total_pages, $page + 1); ?>'" 
                    <?php echo $page >= $total_pages ? 'disabled' : ''; ?>>
                    Next →
                </button>
                <div style="font-size: 12px; color: #999; margin-top: 12px;">
                    Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'nav.php'; ?>

    <script>
        function addReaction(thoughtId, reactionType, button) {
            const formData = new FormData();
            formData.append('action', 'react');
            formData.append('thought_id', thoughtId);
            formData.append('reaction_type', reactionType);

            fetch('share.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Find all reaction buttons for this thought
                    const parent = button.closest('.reactions');
                    const allButtons = parent.querySelectorAll('.reaction-btn');
                    
                    // Update all button states and counts
                    allButtons.forEach(btn => {
                        const countSpan = btn.querySelector('.reaction-count');
                        const emoji = btn.querySelector('.reaction-emoji').textContent;
                        
                        // Get reaction type from button's onclick attribute
                        const onclickAttr = btn.getAttribute('onclick');
                        const match = onclickAttr.match(/'(heart|hug|hurt|moon)'/);
                        const btnReactionType = match ? match[1] : null;
                        
                        if (btnReactionType) {
                            // Update count for this reaction type
                            countSpan.textContent = data.counts[btnReactionType];
                            
                            // Update active state
                            if (data.current_reaction === btnReactionType) {
                                btn.classList.add('active-reaction');
                            } else {
                                btn.classList.remove('active-reaction');
                            }
                        }
                    });
                    
                    // Animate the clicked button
                    button.style.animation = 'pulse 0.3s ease';
                    setTimeout(() => button.style.animation = '', 300);
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // Add pulse animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.15); }
            }
        `;
        document.head.appendChild(style);

        // Toggle music player embed
        function toggleMusicPlayer(button, songData) {
            const container = button.closest('.song-info').querySelector('.music-player-container');
            const isVisible = container.style.display !== 'none';
            
            if (isVisible) {
                container.style.display = 'none';
                button.textContent = '▶ Play';
            } else {
                container.style.display = 'block';
                button.textContent = '⏸ Close';
                
                // Scroll into view
                container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        // Delete thought
        function deleteThought(thoughtId) {
            if (confirm('Are you sure you want to delete this thought? This action cannot be undone.')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('thought_id', thoughtId);

                fetch('share.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    // Reload page after deletion
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to delete thought');
                });
            }
        }
    </script>
</body>
</html>
<?php
// Close database connection after all output
if (isset($conn)) {
    $conn->close();
}

function randomTimestamp() {
    return str_pad(rand(0, 23), 2, '0', STR_PAD_LEFT) . ':' . 
           str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT);
}
?>
