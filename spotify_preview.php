<?php
/**
 * Spotify Preview API Integration
 * Fetches playable 30-second music previews
 */

class SpotifyPreviewProvider {
    private $cache_file = 'spotify_preview_cache.json';
    
    /**
     * Get Spotify preview URL for a song
     * Returns direct MP3 URL that plays for 30 seconds
     */
    public function getPreviewUrl($title, $artist) {
        $cache = $this->loadCache();
        $cache_key = md5(strtolower($title . $artist));
        
        // Check cache first
        if (isset($cache[$cache_key]) && !empty($cache[$cache_key]['url'])) {
            return $cache[$cache_key]['url'];
        }
        
        // Try to fetch from Spotify
        $preview_url = $this->fetchFromSpotify($title, $artist);
        
        if ($preview_url) {
            $cache[$cache_key] = [
                'url' => $preview_url,
                'timestamp' => time()
            ];
            $this->saveCache($cache);
            return $preview_url;
        }
        
        return null;
    }
    
    private function fetchFromSpotify($title, $artist) {
        // Spotify Web API endpoint (no auth needed for public search)
        $search_query = urlencode("$title $artist");
        $url = "https://api.spotify.com/v1/search?q=$search_query&type=track&limit=1";
        
        // Fetch with timeout
        $context = stream_context_create([
            'http' => ['timeout' => 5]
        ]);
        
        try {
            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                return null;
            }
            
            $data = json_decode($response, true);
            
            if (!isset($data['tracks']['items'][0]['preview_url'])) {
                return null;
            }
            
            $preview_url = $data['tracks']['items'][0]['preview_url'];
            
            // Return preview URL if available
            return !empty($preview_url) ? $preview_url : null;
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    private function loadCache() {
        if (file_exists($this->cache_file)) {
            $data = json_decode(file_get_contents($this->cache_file), true);
            return $data ?? [];
        }
        return [];
    }
    
    private function saveCache($cache) {
        file_put_contents($this->cache_file, json_encode($cache, JSON_PRETTY_PRINT));
    }
}

// Test
$spotify = new SpotifyPreviewProvider();
echo "Spotify Preview Provider loaded\n";
echo "Usage: \$spotify->getPreviewUrl('song_title', 'artist_name')\n";
?>
