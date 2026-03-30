<?php
/**
 * Audio proxy - serves audio files with proper CORS headers
 * Prevents browser blocking of external audio sources
 */

// Get the thought ID parameter
$thought_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($thought_id === 0) {
    header('HTTP/1.0 400 Bad Request');
    die('No thought ID specified');
}

// Fetch audio URL from database using thought_id
$conn = new mysqli('localhost', 'root', '', 'unsaid_thoughts');
if ($conn->connect_error) {
    header('HTTP/1.0 500 Internal Server Error');
    die('Connection failed');
}

$stmt = $conn->prepare("SELECT link FROM songs WHERE thought_id = ? AND link IS NOT NULL");
$stmt->bind_param("i", $thought_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $url = $row['link'];
} else {
    header('HTTP/1.0 404 Not Found');
    $conn->close();
    die('No audio found for this thought');
}

$stmt->close();
$conn->close();

// Validate URL
if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
    header('HTTP/1.0 400 Bad Request');
    die('Invalid audio URL');
}

// Fetch the audio file with timeout
$context = stream_context_create([
    'http' => [
        'timeout' => 30,
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

// Read the audio file
$audio_data = @file_get_contents($url, false, $context);

if ($audio_data === false) {
    header('HTTP/1.0 502 Bad Gateway');
    die('Could not fetch audio file');
}

// Set proper CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: audio/mpeg');
header('Content-Length: ' . strlen($audio_data));
header('Cache-Control: public, max-age=86400');
header('Pragma: public');

// Output the audio data
echo $audio_data;
exit;
?>
