<?php
// Security note: This script allows creating files on the server
// Only use in a secure environment with proper authentication
// Remove or secure this file in production

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Authenticate the request (implement proper authentication)
// This is a placeholder - implement real authentication!
function isAuthenticated() {
    // Replace with actual authentication logic
    return true;
}

if (!isAuthenticated()) {
    http_response_code(403);
    echo "Access denied";
    exit;
}

// Check if required parameters are provided
if(isset($_POST['filename']) && isset($_POST['content'])) {
    $filename = $_POST['filename'];
    $content = $_POST['content'];
    
    // Security check: only allow creating .php files in current directory
    $allowedPattern = '/^[a-zA-Z0-9_-]+\.php$/';
    if (!preg_match($allowedPattern, $filename)) {
        http_response_code(400);
        echo "Invalid filename. Only alphanumeric characters, underscores, and hyphens are allowed.";
        exit;
    }
    
    // Write the file
    $result = file_put_contents($filename, $content);
    
    if($result !== false) {
        echo "File $filename created successfully";
    } else {
        http_response_code(500);
        echo "Error: Could not create file. Check directory permissions.";
    }
} else {
    http_response_code(400);
    echo "Error: Missing required parameters (filename and content)";
}
?>