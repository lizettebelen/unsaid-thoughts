<?php
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
    <title>Unsaid Thoughts - Home</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Caveat:wght@400;700&family=Indie+Flower&display=swap');

        /* Mobile-first design */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #FFF9FC 0%, #FFF5F8 100%);
            overflow-x: hidden;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        /* Header */
        header {
            background: linear-gradient(135deg, #FFB6D9 0%, #FF91C5 50%, #FF69B4 100%);
            padding: 2.5rem 1rem;
            text-align: center;
            border-bottom: 2px dashed #FF69B4;
            box-shadow: 0 8px 30px rgba(255, 105, 180, 0.35);
            position: relative;\n            overflow: hidden;
        }

        header::before {
            content: '★';\n            position: absolute;
            top: 10px;
            left: 20px;
            font-size: 1.5rem;
            opacity: 0.4;
            animation: float 4s ease-in-out infinite;
        }

        header::after {
            content: '◈';
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 1.5rem;
            opacity: 0.4;
            animation: float 4s ease-in-out infinite reverse;
        }

        header h1 {
            font-size: 2.8rem;
            color: white;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 0.3rem;
            text-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            position: relative;
            z-index: 1;
            font-family: 'Caveat', cursive;
            font-style: normal;
        }

        header p {
            color: rgba(255, 255, 255, 0.95);
            font-size: 0.9rem;
            font-style: italic;
            font-weight: 500;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* Hero Section */
        .hero-section {
            text-align: center;
            padding: 3rem 1.5rem 2.5rem;
            position: relative;
        }

        .safe-space-badge {
            display: inline-block;
            background: linear-gradient(135deg, rgba(255, 182, 217, 0.3) 0%, rgba(255, 107, 157, 0.3) 100%);
            border: 2px solid #FF69B4;
            border-radius: 50px;
            padding: 0.6rem 1.4rem;
            margin-bottom: 1.5rem;
            font-size: 0.75rem;
            font-weight: 800;
            color: #FF69B4;
            letter-spacing: 2px;
            text-transform: uppercase;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(255, 105, 180, 0.12);
        }

        .hero-title {
            font-size: 2.2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 1.8rem;
            font-style: normal;
            line-height: 1.3;
            letter-spacing: 0.3px;
            font-family: 'Caveat', cursive;
        }

        .write-thought-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            background: linear-gradient(135deg, #FF69B4 0%, #FF1493 100%);
            color: white;
            padding: 1.2rem 2.2rem;
            border-radius: 50px;
            border: none;
            text-decoration: none;
            font-weight: 800;
            font-size: 1.05rem;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 8px 25px rgba(255, 20, 147, 0.3);
            cursor: pointer;
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .write-thought-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .write-thought-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .write-thought-btn:hover {
            transform: translateY(-4px) scale(1.06);
            box-shadow: 0 12px 40px rgba(255, 20, 147, 0.4);
            background: linear-gradient(135deg, #FF1493 0%, #C71585 100%);
        }

        .write-thought-btn:active {
            transform: translateY(-1px);
        }

        .write-thought-btn span:first-child {
            font-size: 1.3rem;
            animation: swing 2s ease-in-out infinite;
        }

        @keyframes swing {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-5deg); }
            75% { transform: rotate(5deg); }
        }

        /* Thoughts Grid */
        .thoughts-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding: 1.5rem;
            max-width: 480px;
            margin: 1.5rem auto;
        }

        /* Featured Section */
        .featured-section {
            background: linear-gradient(135deg, #FFB6D9 0%, #FF91C5 25%, #FF69B4 50%, #FF1493 75%, #DA70D6 100%);
            background-size: 300% 300%;
            border-radius: 24px;
            padding: 3.5rem 2rem;
            margin: 2.5rem 1rem 3rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            color: white;
            box-shadow: 0 20px 60px rgba(255, 105, 180, 0.4), 
                        0 0 40px rgba(255, 20, 147, 0.3),
                        inset 0 0 20px rgba(255, 255, 255, 0.1);
            animation: float 4s ease-in-out infinite, gradientShift 8s ease infinite;
            z-index: 2;
            border: 2px solid rgba(255, 255, 255, 0.15);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .featured-section:hover {
            box-shadow: 0 30px 80px rgba(255, 105, 180, 0.5),
                        0 0 60px rgba(255, 20, 147, 0.5),
                        inset 0 0 30px rgba(255, 255, 255, 0.2);
            transform: translateY(-5px);
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-12px); }
        }

        .featured-section::before {
            content: '★';
            position: absolute;
            top: 20px;
            left: 20px;
            font-size: 2.5rem;
            opacity: 0.6;
            animation: sparkle 2s ease-in-out infinite, spin 6s linear infinite;
            filter: drop-shadow(0 0 8px rgba(255, 255, 255, 0.8));
        }

        .featured-section::after {
            content: '◈';
            position: absolute;
            bottom: 20px;
            right: 20px;
            font-size: 2.5rem;
            opacity: 0.6;
            animation: sparkle 2.5s ease-in-out infinite, spin 6s linear infinite reverse;
            filter: drop-shadow(0 0 8px rgba(255, 255, 255, 0.8));
        }

        @keyframes sparkle {
            0%, 100% { opacity: 0.4; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.1); }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .featured-content {
            position: relative;
            z-index: 1;
        }

        .featured-label {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.7rem 1.6rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 900;
            letter-spacing: 2.5px;
            margin-bottom: 1.2rem;
            text-transform: uppercase;
            border: 2px solid rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(15px);
            box-shadow: 0 4px 20px rgba(255, 255, 255, 0.2);
            animation: labelGlow 3s ease-in-out infinite;
            transition: all 0.3s ease;
        }

        .featured-label:hover {
            background: rgba(255, 255, 255, 0.35);
            border-color: rgba(255, 255, 255, 0.9);
            box-shadow: 0 6px 30px rgba(255, 255, 255, 0.4);
        }

        @keyframes labelGlow {
            0%, 100% { box-shadow: 0 4px 20px rgba(255, 255, 255, 0.2); }
            50% { box-shadow: 0 6px 30px rgba(255, 255, 255, 0.4); }
        }

        .featured-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.1;
            animation: slideDown 1s ease-out;
            font-family: 'Caveat', cursive;
            letter-spacing: 1px;
            text-shadow: 0 4px 15px rgba(0, 0, 0, 0.2),
                         0 0 20px rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .featured-section:hover .featured-title {
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3),
                         0 0 30px rgba(255, 255, 255, 0.5);
            transform: scale(1.05);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .featured-description {
            font-size: 1.05rem;
            opacity: 1;
            margin-bottom: 0;
            line-height: 1.7;
            animation: slideDown 1.2s ease-out 0.1s both;
            font-weight: 500;
            letter-spacing: 0.3px;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }

        .featured-section:hover .featured-description {
            opacity: 1;
            text-shadow: 0 2px 12px rgba(0, 0, 0, 0.2);
        }

        .thought-card {
            background-color: white;
            border: 1.5px solid #FFE5F0;
            border-radius: 16px;
            padding: 0;
            box-shadow: 0 2px 10px rgba(255, 105, 180, 0.08);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .thought-card:hover {
            border-color: #FFB6D9;
            box-shadow: 0 4px 20px rgba(255, 105, 180, 0.15);
            transform: translateY(-2px);
        }

        .card-header {
            background: linear-gradient(135deg, #FFF5F8 0%, #FFE5F0 100%);
            padding: 0.7rem;
            border-bottom: 1px solid #FFE5F0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.8rem;
        }

        .card-mood {
            font-weight: 600;
            color: #FF69B4;
            font-size: 0.9rem;
            background-color: rgba(255, 182, 217, 0.2);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
        }

        .card-timestamp {
            font-size: 0.75rem;
            color: #999;
            font-weight: 500;
        }

        .card-content {
            padding: 0.9rem;
            font-size: 0.9rem;
            line-height: 1.4;
            color: #444;
            font-style: italic;
            word-break: break-word;
        }

        .card-song {
            background: linear-gradient(135deg, #FFF8FB 0%, #FFF0F7 100%);
            padding: 0.8rem;
            border-top: 1px solid #FFE5F0;
            border-bottom: 1px solid #FFE5F0;
            font-size: 0.85rem;
            display: flex;
            gap: 0.6rem;
            align-items: flex-start;
            transition: all 0.3s ease;
        }

        .card-song-icon {
            font-size: 1.1rem;
            min-width: 20px;
        }

        .card-song-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .card-song-title {
            font-weight: 700;
            color: #333;
            margin-bottom: 0.2rem;
            font-size: 0.95rem;
            line-height: 1.3;
        }

        .card-song-artist {
            color: #999;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .card-reactions {
            padding: 0.6rem;
            display: flex;
            justify-content: space-between;
            gap: 0.3rem;
            border-top: 1px solid #FFE5F0;
        }

        .reaction-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
            padding: 0.6rem 0.8rem;
            background-color: #FFF5F8;
            border: 1px solid #FFE5F0;
            border-radius: 12px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s ease;
            flex: 1;
            color: #666;
        }

        .reaction-btn:hover {
            background-color: #FFE5F0;
            border-color: #FFB6D9;
            transform: scale(1.05);
        }

        .reaction-icon {
            font-size: 1rem;
        }

        .reaction-count {
            font-size: 0.8rem;
            min-width: 12px;
        }

        /* Loading state */
        .loading-container {
            text-align: center;
            padding: 3rem 1rem;
        }

        .loading-spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 3px solid #FFE5F0;
            border-top: 3px solid #FFB6D9;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            color: #999;
            font-size: 0.95rem;
            font-style: italic;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }

        .empty-state h2 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .empty-state p {
            color: #999;
            font-size: 0.95rem;
        }

        /* End of scroll message */
        .end-scroll-message {
            text-align: center;
            padding: 1.5rem 1rem;
            color: #ccc;
            font-style: italic;
            font-size: 0.9rem;
        }

        .explore-more-btn {
            display: block;
            margin: 1.5rem auto;
            padding: 0.9rem 2rem;
            background: linear-gradient(135deg, #FFB6D9 0%, #FF91C5 100%);
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            box-shadow: 0 4px 15px rgba(255, 105, 180, 0.2);
        }

        .explore-more-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 105, 180, 0.3);
        }

        /* Container */
        .container {
            padding-bottom: 90px;
        }

        /* Error message */
        .error-message {
            background-color: #FFEBEE;
            color: #C62828;
            border: 1px solid #FFCDD2;
            padding: 1.2rem;
            border-radius: 12px;
            margin: 1.5rem;
            text-align: center;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .error-message strong {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        /* Responsive */
        @media (max-width: 480px) {
            header h1 {
                font-size: 1.8rem;
            }

            .hero-title {
                font-size: 1.6rem;
            }

            .write-thought-btn {
                padding: 0.9rem 1.5rem;
                font-size: 0.95rem;
            }

            .thoughts-container {
                max-width: 100%;
                padding: 1rem;
                gap: 0.8rem;
            }



            .card-header {
                padding: 0.8rem;
            }

            .card-content {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php
        $header_title = '➹ Unsaid Thoughts';
        $header_subtitle = 'Everything you never said';
        include 'header.php';
    ?>

    <div class="container">
        <!-- Featured Section -->
        <div class="featured-section">
            <div class="featured-content">
                <span class="featured-label">✦ Your Safe Haven ✦</span>
                <h2 class="featured-title">Share What You're Really Feeling</h2>
                <p class="featured-description">No judgment, no names, just authentic thoughts and feelings from people like you. Let it all out!</p>
            </div>
        </div>

        <!-- Hero Section -->
        <div class="hero-section">
            <div class="safe-space-badge">Welcome to Your Safe Space</div>
            <h2 class="hero-title">Explore What Others Are Thinking</h2>
            <a href="explore.php" class="write-thought-btn">
                <span>⊡</span>
                <span>Browse All Thoughts</span>
            </a>
        </div>

        <!-- Error Message -->
        <?php if ($error): ?>
            <div class="error-message">
                ⚠️ <strong>Database Error:</strong><br>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Thoughts Container -->
        <div class="thoughts-container" id="thoughtsContainer" style="display: none;">
            <?php if (count($thoughts) === 0): ?>
                <div class="empty-state">
                    <h2>◈ No thoughts yet</h2>
                    <p>Be the first to share what's on your mind!</p>
                </div>
            <?php else: ?>
                <?php foreach ($thoughts as $thought): ?>
                    <div class="thought-card">
                        <div class="card-header">
                            <span class="card-mood"><?php echo $thought['mood']; ?></span>
                            <span class="card-timestamp"><?php echo formatDate($thought['created_at']); ?></span>
                        </div>
                        <div class="card-content">
                            "<?php echo $thought['content']; ?>"
                        </div>
                        <?php if ($thought['song']): ?>
                            <div class="card-song">
                                <div class="card-song-icon">🎵</div>
                                <div class="card-song-info" style="flex: 1;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                                        <div style="flex: 1;">
                                            <div class="card-song-title">"<?php echo $thought['song']['title']; ?>"</div>
                                            <div class="card-song-artist"><?php echo $thought['song']['artist']; ?></div>
                                        </div>
                                        <button class="btn-play-toggle" onclick="toggleMusicPlayer(this, <?php echo htmlspecialchars(json_encode($thought['song'])); ?>)"
                                                style="padding: 6px 14px; background: linear-gradient(135deg, #FF0000 0%, #ff4444 100%); color: white; border: none; border-radius: 18px; font-size: 12px; font-weight: 600; cursor: pointer; margin-left: 8px; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(255,0,0,0.2);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(255,0,0,0.3)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(255,0,0,0.2)';">
                                            ▶ Play
                                        </button>
                                    </div>
                                    <!-- YouTube Music Player -->
                                    <div class="music-player-container" style="display: none; width: calc(100% - 16px); max-width: 100%; margin-left: auto; margin-right: auto; background: linear-gradient(135deg, #FF0000 0%, #ff6b6b 100%); border-radius: 14px; padding: 18px; margin-top: 12px; box-shadow: 0 6px 20px rgba(255,0,0,0.25); position: relative; overflow: hidden;">
                                        <!-- Decorative elements -->
                                        <div style="position: absolute; top: -30px; right: -30px; width: 80px; height: 80px; background: rgba(255,255,255,0.05); border-radius: 50%; pointer-events: none;"></div>
                                        <div style="display: flex; flex-direction: column; gap: 14px; position: relative; z-index: 1;">
                                            <div style="text-align: center; color: white;">
                                                <div style="font-size: 10px; letter-spacing: 1px; opacity: 0.85; text-transform: uppercase; font-weight: 700; margin-bottom: 6px;">🎵 Now Playing</div>
                                                <div style="font-size: 15px; font-weight: 800; line-height: 1.3; margin-bottom: 4px;"><?php echo htmlspecialchars($thought['song']['title']); ?></div>
                                                <div style="font-size: 12px; opacity: 0.9; font-weight: 500;"><?php echo htmlspecialchars($thought['song']['artist']); ?></div>
                                            </div>
                                            <a href="https://music.youtube.com/search?q=<?php echo urlencode($thought['song']['title'] . ' ' . $thought['song']['artist']); ?>" 
                                               target="_blank"
                                               style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 11px 18px; background-color: white; color: #FF0000; text-decoration: none; border-radius: 24px; font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 3px 10px rgba(0,0,0,0.15); text-transform: uppercase; letter-spacing: 0.5px;" onmouseover="this.style.backgroundColor='#f5f5f5'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 5px 15px rgba(0,0,0,0.2)';" onmouseout="this.style.backgroundColor='white'; this.style.transform='translateY(0)'; this.style.boxShadow='0 3px 10px rgba(0,0,0,0.15)';">
                                                <span style="font-size: 14px;">▶</span> Play on YouTube Music
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="card-reactions">
                            <button class="reaction-btn" data-thought-id="<?php echo $thought['id']; ?>" data-reaction="heart">
                                <span class="reaction-icon">❤️</span>
                                <span class="reaction-count"><?php echo $thought['reactions']['heart']; ?></span>
                            </button>
                            <button class="reaction-btn" data-thought-id="<?php echo $thought['id']; ?>" data-reaction="hug">
                                <span class="reaction-icon">🫂</span>
                                <span class="reaction-count"><?php echo $thought['reactions']['hug']; ?></span>
                            </button>
                            <button class="reaction-btn" data-thought-id="<?php echo $thought['id']; ?>" data-reaction="hurt">
                                <span class="reaction-icon">🥀</span>
                                <span class="reaction-count"><?php echo $thought['reactions']['hurt']; ?></span>
                            </button>
                            <button class="reaction-btn" data-thought-id="<?php echo $thought['id']; ?>" data-reaction="moon">
                                <span class="reaction-icon">🌙</span>
                                <span class="reaction-count"><?php echo $thought['reactions']['moon']; ?></span>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="end-scroll-message">✦ You've scrolled through all the moods for now</div>
            <?php endif; ?>
        </div>

        <!-- Explore More Button -->
        <button class="explore-more-btn" onclick="location.reload()">Explore more moods</button>
    </div>

    <?php include 'nav.php'; ?>

    <script>
        // Handle reaction clicks
        document.querySelectorAll('.reaction-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const thoughtId = this.dataset.thoughtId;
                const reactionType = this.dataset.reaction;

                try {
                    const response = await fetch('/unsaidthoughts-/php/react.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            thought_id: thoughtId, 
                            reaction_type: reactionType 
                        })
                    });

                    const result = await response.json();
                    
                    if (result.status === 'success') {
                        this.querySelector('.reaction-count').textContent = result.count;
                        this.style.backgroundColor = '#FFB6D9';
                        setTimeout(() => {
                            this.style.backgroundColor = '#FFF5F8';
                        }, 300);
                    }
                } catch (error) {
                    console.error('Error:', error);
                }
            });
        });

        // Toggle music player embed
        function toggleMusicPlayer(button, songData) {
            const container = button.closest('.card-song-info').querySelector('.music-player-container');
            const isVisible = container.style.display !== 'none';
            
            if (isVisible) {
                container.style.display = 'none';
                button.textContent = '▶ Play';
                button.style.background = 'linear-gradient(135deg, #FF0000 0%, #ff4444 100%)';
            } else {
                container.style.display = 'block';
                button.textContent = '⏸ Close';
                button.style.background = 'linear-gradient(135deg, #FF6B6B 0%, #FF0000 100%)';
                
                // Scroll into view
                container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
    </script>
</body>
</html>
