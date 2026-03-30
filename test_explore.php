<?php
// Test explore.php to make sure it runs without database errors
$_GET['filter'] = 'all';
$_GET['page'] = 1;

ob_start();
include 'explore.php';
$output = ob_get_clean();

if (strpos($output, 'Fatal error') !== false || strpos($output, 'not work') !== false) {
    echo "❌ ERROR in explore.php:\n";
    echo $output;
} else {
    echo "✅ explore.php runs successfully!\n";
    echo "Output length: " . strlen($output) . " bytes\n";
    // Check for expected content
    if (strpos($output, 'EXPLORE') !== false) {
        echo "✅ Page title found\n";
    }
    if (strpos($output, 'thought') !== false) {
        echo "✅ Thoughts displayed\n";
    }
}
?>
