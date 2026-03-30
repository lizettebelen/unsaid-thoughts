<?php
/**
 * User Session Management
 * Creates/manages anonymous user IDs for tracking reactions
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate or retrieve user ID
if (!isset($_SESSION['user_id'])) {
    // Create unique user ID (combination of session and device fingerprint)
    $user_id = bin2hex(random_bytes(16)); // 32-character hex string
    $_SESSION['user_id'] = $user_id;
    
    // Also set as cookie for persistence across session resets
    setcookie('unsaid_user_id', $user_id, time() + (365 * 24 * 60 * 60), '/');
} else {
    $user_id = $_SESSION['user_id'];
}

// Function to get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}
?>
