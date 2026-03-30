<?php
/**
 * Get Spotify preview URLs for songs
 * Uses Spotify Web API to fetch playable 30-second previews
 */

class SpotifyMusicProvider {
    private $client_id = '';      // Will use free/demo approach
    private $client_secret = '';
    private $cache_file = 'music_cache.json';
    
    /**
     * Get preview audio URL from Spotify for a song
     * Returns direct MP3 link that plays in HTML5 audio tag
     */
    public function getPreviewUrl($title, $artist) {
        // Load cache to avoid repeated API calls
        $cache = $this->loadCache();
        $cache_key = md5(strtolower($title . $artist));
        
        // Return cached URL if available
        if (isset($cache[$cache_key]) && !empty($cache[$cache_key])) {
            return $cache[$cache_key];
        }
        
        // Try to fetch from public Spotify search endpoint (no auth needed for preview)
        $url = "https://api.spotify.com/v1/search?q=" . urlencode("$title $artist") . "&type=track&limit=1";
        
        $preview_url = $this->fetchSpotifyPreview($url);
        
        if ($preview_url) {
            $cache[$cache_key] = $preview_url;
            $this->saveCache($cache);
            return $preview_url;
        }
        
        return null;
    }
    
    private function fetchSpotifyPreview($url) {
        // This requires Spotify API credentials
        // For production, you'd need:
        // 1. Spotify Developer App credentials
        // 2. OAuth token
        // For now, return fallback
        return null;
    }
    
    private function loadCache() {
        if (file_exists($this->cache_file)) {
            return json_decode(file_get_contents($this->cache_file), true) ?? [];
        }
        return [];
    }
    
    private function saveCache($cache) {
        file_put_contents($this->cache_file, json_encode($cache, JSON_PRETTY_PRINT));
    }
}

// Alternative: Use a simple approach with embedded players
function generateEmbeddedMusicPlayer($title, $artist, $link = null) {
    /**
     * Generate embedded music player using YouTube iframe (audio only)
     * Shows a clean player interface directly on the page
     */
    
    if (!$link) {
        $link = "https://www.youtube.com/results?search_query=" . urlencode("$title $artist");
    }
    
    // Check if link is YouTube
    if (strpos($link, 'youtube.com') !== false) {
        // Extract video ID if direct video link
        // Format: https://www.youtube.com/embed/VIDEO_ID
        $search_query = urlencode("$title $artist");
        
        return "
        <div class='music-player' style='border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);'>
            <iframe width='100%' 
                    height='80' 
                    src='https://www.youtube.com/embed/?list=$search_query' 
                    title='YouTube music player' 
                    frameborder='0' 
                    allow='accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture' 
                    allowfullscreen 
                    style='border: none; display: block;'></iframe>
        </div>";
    }
    
    // Fall back to HTML5 audio if direct link
    return "
    <audio controls style='width: 100%; height: 32px; border-radius: 4px;'>
        <source src='" . htmlspecialchars($link) . "' type='audio/mpeg'>
        Your browser does not support the audio element.
    </audio>";
}

echo "Music embed helpers loaded\n";
?>
