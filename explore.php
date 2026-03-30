<?php
// Set timezone for consistent timestamp handling
date_default_timezone_set('Asia/Manila');

// Include session config for user tracking
require_once 'config_session.php';

// Database connection
$host = 'localhost';
$port = 3306;
$db = 'unsaid_thoughts';
$user = 'root';
$password = '';

$error = null;
$thoughts = [];
$success = isset($_GET['success']) ? true : false;

try {
    $conn = @new mysqli($host, $user, $password, $db, $port);
    
    if ($conn->connect_error) {
        throw new Exception('MySQL Connection Error: ' . $conn->connect_error . 
                          '<br><br>➡️ <strong>Fix:</strong> Start MySQL in XAMPP Control Panel');
    }
    
    $conn->set_charset("utf8mb4");
    
    // Fetch all thoughts with songs
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
    ";
    
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }
    
    // Process results into structured array
    $thoughts = [];
    while ($row = $result->fetch_assoc()) {
        $thought_id = $row['id'];
        
        if (!isset($thoughts[$thought_id])) {
            $thoughts[$thought_id] = [
                'id' => $row['id'],
                'content' => htmlspecialchars($row['content'], ENT_QUOTES, 'UTF-8'),
                'mood' => htmlspecialchars($row['mood'] ?: 'Anonymous', ENT_QUOTES, 'UTF-8'),
                'nickname' => htmlspecialchars($row['nickname'] ?: 'Anonymous', ENT_QUOTES, 'UTF-8'),
                'created_at' => $row['created_at'],
                'song' => null,
                'reactions' => [
                    'heart' => 0,
                    'hug' => 0,
                    'hurt' => 0,
                    'moon' => 0
                ]
            ];
        }
        
        if ($row['song_id'] !== null) {
            $thoughts[$thought_id]['song'] = [
                'title' => htmlspecialchars($row['song_title'], ENT_QUOTES, 'UTF-8'),
                'artist' => htmlspecialchars($row['song_artist'], ENT_QUOTES, 'UTF-8'),
                'link' => htmlspecialchars($row['song_link'], ENT_QUOTES, 'UTF-8')
            ];
        }
    }
    
    // Fetch reaction counts for all thoughts
    foreach ($thoughts as $thought_id => $thought) {
        $reactions_query = "SELECT type, COUNT(*) as count FROM reactions WHERE thought_id = ? GROUP BY type";
        $reactions_stmt = $conn->prepare($reactions_query);
        $reactions_stmt->bind_param("i", $thought_id);
        $reactions_stmt->execute();
        $reactions_result = $reactions_stmt->get_result();
        while ($reaction_row = $reactions_result->fetch_assoc()) {
            $thoughts[$thought_id]['reactions'][$reaction_row['type']] = (int)$reaction_row['count'];
        }
    }
    
    $thoughts = array_values($thoughts);
    $conn->close();
    
} catch (Exception $e) {
    $error = $e->getMessage();
    $thoughts = [];
}

// Helper function to determine music platform from URL
function getMusicPlatform($url) {
    if (!$url) return 'Listen';
    
    $url = strtolower($url);
    
    if (strpos($url, 'spotify.com') !== false) {
        return 'Listen on Spotify';
    } elseif (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
        return 'Listen on YouTube';
    } elseif (strpos($url, 'soundcloud.com') !== false) {
        return 'Listen on SoundCloud';
    } elseif (strpos($url, 'apple.com') !== false || strpos($url, 'music.apple.com') !== false) {
        return 'Listen on Apple Music';
    } elseif (strpos($url, 'tidal.com') !== false) {
        return 'Listen on Tidal';
    } elseif (strpos($url, 'deezer.com') !== false) {
        return 'Listen on Deezer';
    }
    
    return 'Listen';
}

// Helper function to format dates
function formatDate($dateString) {
    $date = new DateTime($dateString);
    $now = new DateTime();
    $diff = $now->diff($date);
    
    if ($diff->days == 0 && $diff->h == 0 && $diff->i < 1) return 'now';
    if ($diff->days == 0 && $diff->h == 0) return $diff->i . 'm ago';
    if ($diff->days == 0) return $diff->h . 'h ago';
    if ($diff->days < 7) return $diff->days . 'd ago';
    
    return $date->format('M d');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore - Unsaid Thoughts</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Caveat:wght@400;700&family=Indie+Flower&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #FFF9FC 0%, #FFF5F8 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            min-height: 100vh;
            padding-bottom: 2rem;
        }

        /* Navigation */
        .nav-links {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }

        .nav-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.5);
            padding: 0.6rem 1.2rem;
            border-radius: 20px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: white;
            transform: translateY(-2px);
        }

        /* Container */
        .container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 0 1rem 100px 1rem;
        }

        /* Success Message */
        .success-message {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
            animation: slideIn 0.5s ease;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .success-message::before {
            content: '✓';
            font-size: 1.5rem;
            font-weight: bold;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Error Message */
        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border-left: 4px solid #c62828;
        }

        /* Thought Card */
        .thought-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 20px rgba(255, 105, 180, 0.1);
            border: 1px solid #FFE5F0;
            transition: all 0.3s ease;
            animation: fadeIn 0.5s ease;
        }

        .thought-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(255, 105, 180, 0.15);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Thought Header */
        .thought-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.8rem;
            gap: 0.5rem;
        }

        .thought-meta {
            flex: 1;
        }

        .thought-nickname {
            font-weight: 700;
            color: #FF69B4;
            font-family: 'Caveat', cursive;
            font-size: 1.3rem;
            letter-spacing: 0.3px;
        }

        .thought-mood {
            font-size: 0.75rem;
            color: #999;
            margin-top: 0.2rem;
        }

        .thought-time {
            font-size: 0.8rem;
            color: #BBB;
            text-align: right;
        }

        /* Thought Content */
        .thought-content {
            color: #333;
            line-height: 1.6;
            margin-bottom: 1rem;
            font-size: 0.95rem;
            word-wrap: break-word;
        }

        /* Song Info */
        .song-info {
            background: linear-gradient(135deg, #FFE5F0 0%, #FFF0F5 100%);
            padding: 0.8rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #FF69B4;
        }

        .song-title {
            font-weight: 700;
            color: #FF69B4;
            font-size: 0.95rem;
        }

        .song-artist {
            color: #666;
            font-size: 0.85rem;
            margin-top: 0.2rem;
        }

        .song-link {
            display: inline-block;
            margin-top: 0.5rem;
            padding: 0.4rem 0.8rem;
            background: linear-gradient(135deg, #FF69B4 0%, #FF91C5 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }

        .song-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 105, 180, 0.3);
        }

        /* Reactions */
        .reactions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            border-top: 1px solid #FFE5F0;
            padding-top: 1rem;
        }

        .reaction-btn {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.5rem 0.8rem;
            background: #FFF5FB;
            border: 1.5px solid #FFE5F0;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            flex: 1;
            min-width: 100px;
            justify-content: center;
        }

        .reaction-btn:hover {
            border-color: #FF69B4;
            background: #FFE5F0;
            transform: scale(1.05);
        }

        .reaction-btn.active {
            background: linear-gradient(135deg, #FF69B4 0%, #FF91C5 100%);
            color: white;
            border-color: #FF69B4;
        }

        .reaction-count {
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* No Thoughts */
        .no-thoughts {
            text-align: center;
            padding: 3rem 1.5rem;
            color: #999;
        }

        .no-thoughts p {
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }

        /* Mobile Responsive */
        @media (max-width: 600px) {
            header h1 {
                font-size: 2rem;
            }

            .container {
                max-width: 100%;
                padding: 0 0.8rem;
            }

            .thought-card {
                padding: 1rem;
            }

            .reactions {
                gap: 0.3rem;
            }

            .reaction-btn {
                padding: 0.4rem 0.6rem;
                font-size: 0.75rem;
                min-width: 80px;
            }

            .nav-links {
                gap: 0.5rem;
            }

            .nav-btn {
                padding: 0.5rem 0.8rem;
                font-size: 0.85rem;
            }
        }

        /* Loading state */
        .loading {
            text-align: center;
            padding: 2rem;
            color: #FF69B4;
        }

        .loading::after {
            content: '';
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 105, 180, 0.3);
            border-top-color: #FF69B4;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>
<body>
    <?php
        $header_title = '◎ Explore Thoughts ◎';
        $header_subtitle = 'Discover what others are thinking...';
        include 'header.php';
    ?>

    <div class="container">
        <?php if ($success): ?>
            <div class="success-message">
                Your thought has been posted successfully! ✦
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message">
                <strong>Error:</strong> <?php echo nl2br($error); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($thoughts) && !$error): ?>
            <div class="no-thoughts">
                <p>No thoughts yet... Be the first to share! ✧</p>
                <a href="create.php" class="nav-btn">Share Your First Thought</a>
            </div>
        <?php else: ?>
            <?php foreach ($thoughts as $index => $thought): ?>
                <div class="thought-card" <?php echo $index >= 5 ? 'style="display:none;"' : ''; ?> data-thought-index="<?php echo $index; ?>">
                    <div class="thought-header">
                        <div class="thought-meta">
                            <div class="thought-nickname">
                                ♡ <?php echo $thought['nickname']; ?>
                            </div>
                            <div class="thought-mood">
                                Mood: <?php echo $thought['mood']; ?>
                            </div>
                        </div>
                        <div class="thought-time">
                            <?php echo formatDate($thought['created_at']); ?>
                        </div>
                    </div>

                    <div class="thought-content">
                        <?php echo nl2br($thought['content']); ?>
                    </div>

                    <?php if ($thought['song']): ?>
                        <div class="song-info">
                            <div class="song-title">✦ <?php echo $thought['song']['title']; ?></div>
                            <div class="song-artist">by <?php echo $thought['song']['artist']; ?></div>
                            <?php if ($thought['song']['link']): ?>
                                <a href="<?php echo $thought['song']['link']; ?>" target="_blank" class="song-link">
                                    <?php echo getMusicPlatform($thought['song']['link']); ?> ▸
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="reactions">
                        <button class="reaction-btn" onclick="addReaction(<?php echo $thought['id']; ?>, 'heart')">
                            <span class="reaction-icon">❤️</span>
                            <span class="reaction-count"><?php echo $thought['reactions']['heart']; ?></span>
                        </button>
                        <button class="reaction-btn" onclick="addReaction(<?php echo $thought['id']; ?>, 'hug')">
                            <span class="reaction-icon">🫂</span>
                            <span class="reaction-count"><?php echo $thought['reactions']['hug']; ?></span>
                        </button>
                        <button class="reaction-btn" onclick="addReaction(<?php echo $thought['id']; ?>, 'hurt')">
                            <span class="reaction-icon">🥀</span>
                            <span class="reaction-count"><?php echo $thought['reactions']['hurt']; ?></span>
                        </button>
                        <button class="reaction-btn" onclick="addReaction(<?php echo $thought['id']; ?>, 'moon')">
                            <span class="reaction-icon">🌙</span>
                            <span class="reaction-count"><?php echo $thought['reactions']['moon']; ?></span>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (count($thoughts) > 5): ?>
                <div style="text-align: center; margin-top: 2rem;">
                    <button id="seeMoreBtn" class="nav-btn" onclick="toggleMoreThoughts()" style="background: linear-gradient(135deg, #FFB6D9 0%, #FF91C5 100%); color: white; border: none; padding: 0.8rem 2rem;">
                        ✦ See More
                    </button>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php include 'nav.php'; ?>

    <script>
        let showingAll = false;
        
        function toggleMoreThoughts() {
            const hiddenThoughts = document.querySelectorAll('[data-thought-index]');
            const btn = document.getElementById('seeMoreBtn');
            
            showingAll = !showingAll;
            
            hiddenThoughts.forEach(thought => {
                const index = parseInt(thought.getAttribute('data-thought-index'));
                if (index >= 5) {
                    thought.style.display = showingAll ? 'block' : 'none';
                }
            });
            
            btn.textContent = showingAll ? '✦ Show Less' : '✦ See More';
        }
        
        function addReaction(thoughtId, reactionType) {
            fetch('add_reaction.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    thought_id: thoughtId,
                    type: reactionType
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload to show updated counts
                    location.reload();
                } else {
                    alert(data.message || 'Error adding reaction');
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>
