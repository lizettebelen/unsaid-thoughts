<?php
/**
 * Get Spotify track embed code for a song
 * Creates embedded Spotify player that plays music directly on the page
 */

function getSpotifyEmbed($title, $artist) {
    // Format: spotify:track:TRACK_ID or use Spotify embed URL
    // For now, we'll use a search-based approach with Spotify embed
    
    // Spotify embed URL format:
    // https://open.spotify.com/embed/track/TRACK_ID
    
    // Generate Spotify search URL that can be embedded
    $search_query = urlencode("$title $artist");
    
    // Return a formatted embed code
    return "spotify_search:" . $search_query;
}

function generateMusicPlayerEmbed($title, $artist, $song_id = null) {
    /**
     * Create an embedded music player
     * Options:
     * 1. Spotify iframe embed
     * 2. YouTube iframe (audio only)
     * 3. Custom HTML5 audio with streaming service
     */
    
    $search_query = urlencode("$title $artist");
    
    // Create HTML for embedded player (will use Spotify or alternative)
    $html = "
    <div class='music-player-embed' data-title='" . htmlspecialchars($title) . "' data-artist='" . htmlspecialchars($artist) . "'>
        <iframe allow='autoplay *; clipboard-write; encrypted-media fullscreen; picture-in-picture' 
                loading='lazy' 
                role='img' 
                src='https://open.spotify.com/embed/search/$search_query?utm_source=unsaid_thoughts' 
                style='width: 100%; height: 380px; border-radius: 8px; border: none;'></iframe>
    </div>
    ";
    
    return $html;
}

// Test
echo "Spotify embed functions ready\n";
echo "Usage: generateMusicPlayerEmbed('song_title', 'artist_name')\n";
?>
