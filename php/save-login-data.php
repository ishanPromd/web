<?php
// Simple PHP script to save login data to login.txt file

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if data was sent
if(isset($_POST['data'])) {
    // Get the data
    $logData = $_POST['data'];
    
    // Path to login.txt file (make sure the directory is writable)
    $filePath = 'login.txt';
    
    // Append data to the file
    $result = file_put_contents($filePath, $logData, FILE_APPEND | LOCK_EX);
    
    if($result !== false) {
        echo "Data successfully saved to login.txt";
    } else {
        http_response_code(500);
        echo "Error: Could not write to file. Check file permissions.";
    }
} else {
    http_response_code(400);
    echo "Error: No data received";
}
?>