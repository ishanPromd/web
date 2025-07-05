<?php
// At the VERY TOP of index.php for debugging:
// For production, ensure these are OFF or logging to a file only.
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Define paths
// Using __DIR__ creates a 'Documents' folder relative to this script's location.
// This is generally safer and easier for permissions.
// The web server user (e.g., www-data) needs write permission in the directory
// where this script resides if it needs to create the 'Documents' folder itself.
// Typically, if the script can execute, it can create subdirectories.
$serverFileSystemBaseUploadDir = __DIR__ . '/Documents/';
$webAccessibleBaseUploadPath = 'Documents/';


// Function to parse TXT file for status, used only within get_Document_statuses action
function parseTxtFileForStatusInIndex($txtFilePath) {
    $status = 'Pending'; // Default status
    if (file_exists($txtFilePath) && is_readable($txtFilePath)) {
        $lines = file($txtFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines !== false) {
            foreach ($lines as $line) {
                if (stripos($line, 'Status:') === 0) {
                    $parsedStatus = trim(substr($line, strlen('Status:')));
                    if (in_array($parsedStatus, ['Accepted', 'Rejected', 'Pending'])) {
                        $status = $parsedStatus;
                        break;
                    }
                }
            }
        } else {
             error_log("parseTxtFileForStatusInIndex (index.php): Failed to read lines from: " . $txtFilePath);
        }
    } else {
         error_log("parseTxtFileForStatusInIndex (index.php): File not found or not readable: " . $txtFilePath);
    }
    return $status;
}


// --- ACTION: get_Document_statuses ---
// Handles POST requests specifically for fetching Document statuses via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'get_Document_statuses') {

    ini_set('display_errors', 0); // Suppress errors from being sent to the client
    error_reporting(E_ALL); // Report all errors
    ini_set('log_errors', 1); // Log errors
    // Optionally: ini_set('error_log', '/path/to/your/php-error.log'); // Specify custom log file

    header('Content-Type: application/json; charset=utf-8');

    $responseStatuses = [];
    $rawInput = file_get_contents('php://input');
    $requestData = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("get_Document_statuses (index.php): Invalid JSON payload received. Error: " . json_last_error_msg() . ". Payload: " . $rawInput);
        echo json_encode(['error' => 'Invalid request format: Bad JSON.', 'details' => json_last_error_msg(), 'statuses' => []]);
        exit;
    }

    if (isset($requestData['filePaths']) && is_array($requestData['filePaths'])) {
        $realBaseDir = realpath($serverFileSystemBaseUploadDir);
        if (!$realBaseDir) {
            error_log("get_Document_statuses (index.php): CRITICAL - Base upload directory is invalid or does not exist: " . $serverFileSystemBaseUploadDir . " (Resolved: " . ($realBaseDir ?: 'false') . ")");
            foreach ($requestData['filePaths'] as $webFilePath) {
                 if (!empty($webFilePath) && is_string($webFilePath)) $responseStatuses[$webFilePath] = 'Server Config Error';
            }
        } else {
            foreach ($requestData['filePaths'] as $webFilePath) {
                if (empty($webFilePath) || !is_string($webFilePath)) {
                    error_log("get_Document_statuses (index.php): Received empty or non-string filePath in list.");
                    if (!empty($webFilePath) && is_scalar($webFilePath)) {
                        $responseStatuses[(string)$webFilePath] = 'Path Error: Invalid type';
                    }
                    continue;
                }

                $expectedPrefix = $webAccessibleBaseUploadPath ? rtrim($webAccessibleBaseUploadPath, '/') . '/' : '';
                if ($expectedPrefix && strpos($webFilePath, $expectedPrefix) === 0 && strpos($webFilePath, '..') === false) {
                    $relativePath = substr($webFilePath, strlen($expectedPrefix));
                    $parts = explode('/', $relativePath, 2);

                    if (count($parts) === 2) {
                        $userFolder = preg_replace('/[^a-zA-Z0-9_-]/', '', $parts[0]);
                        $DocumentFileFullName = preg_replace('/[^a-zA-Z0-9_.-]/', '', $parts[1]);

                        if(empty($userFolder) || empty($DocumentFileFullName)){
                             $responseStatuses[$webFilePath] = 'Path Error: Invalid Chars';
                             error_log("get_Document_statuses (index.php): Invalid characters in path components derived from: " . $webFilePath);
                             continue;
                        }

                        $txtFileName = pathinfo($DocumentFileFullName, PATHINFO_FILENAME) . '.txt';
                        $txtFilePathOnServer = rtrim($serverFileSystemBaseUploadDir, '/') . '/' . $userFolder . '/' . $txtFileName;
                        $realTxtPath = realpath($txtFilePathOnServer);

                        if ($realTxtPath && strpos($realTxtPath, $realBaseDir) === 0 && file_exists($realTxtPath)) {
                             $responseStatuses[$webFilePath] = parseTxtFileForStatusInIndex($realTxtPath);
                        } else {
                            $responseStatuses[$webFilePath] = 'Not Found'; // Or 'Status Unavailable'
                            error_log("get_Document_statuses (index.php): TXT file check failed for client path '" . $webFilePath . "'. Constructed server path: '" . $txtFilePathOnServer . "'. Real TxtPath: '" . ($realTxtPath ?: 'false') . "'. File exists: ".(file_exists($txtFilePathOnServer) ? 'yes':'no'));
                        }
                    } else {
                         $responseStatuses[$webFilePath] = 'Path Error: Structure';
                         error_log("get_Document_statuses (index.php): Invalid path structure for webFilePath: " . $webFilePath);
                    }
                } else {
                    $responseStatuses[$webFilePath] = 'Security Error: Path';
                    error_log("get_Document_statuses (index.php): Security error or invalid base for webFilePath: '" . $webFilePath . "'. Expected base prefix: '" . $expectedPrefix . "'");
                }
            }
        }
    } else {
        error_log("get_Document_statuses (index.php): 'filePaths' key missing or not an array in JSON payload. Payload: " . $rawInput);
        echo json_encode(['error' => "Invalid request: 'filePaths' array not found or malformed.", 'statuses' => []]);
        exit;
    }

    $json_output = json_encode($responseStatuses);

    if ($json_output === false) {
        $json_error_code = json_last_error();
        $json_error_msg = json_last_error_msg();
        error_log("get_Document_statuses (index.php): CRITICAL - json_encode failed. Error Code: " . $json_error_code . ", Message: " . $json_error_msg . ". Data attempted: " . print_r($responseStatuses, true));
        echo json_encode([
            'error' => 'Server error: Could not encode JSON response for statuses.',
            'details' => 'Error Code ' . $json_error_code,
            'statuses' => [] // Provide an empty statuses object on failure
        ]);
    } else {
        echo $json_output;
    }
    exit;
}


// --- ACTION: uploadFile (Main Upload Handler) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['DocumentFile']) && !isset($_GET['action'])) {
    ini_set('display_errors', 0); // Suppress errors from being sent to the client
    error_reporting(E_ALL);     // Report all errors
    ini_set('log_errors', 1);   // Log errors
    // Optionally: ini_set('error_log', '/path/to/your/php-error.log'); // Specify custom log file

    header('Content-Type: application/json; charset=utf-8');
    $response = ['success' => false, 'message' => 'Upload initialization error.'];

    // **************************************************************************************
    // IMPORTANT NOTE ON FILE UPLOAD LIMITS:
    // Removing the script-level file size check below does NOT guarantee unlimited uploads.
    // PHP has server-wide configurations in `php.ini` that dictate maximum file sizes.
    // You MUST configure these settings in your `php.ini` file for larger uploads:
    //
    // 1. `upload_max_filesize`: (e.g., "100M", "1G")
    //    Sets the maximum size of a single uploaded file.
    //
    // 2. `post_max_size`: (e.g., "105M", "1.1G")
    //    Sets the maximum size of POST data that PHP will accept. This MUST be larger
    //    than `upload_max_filesize`.
    //
    // 3. `memory_limit`: (e.g., "256M", "1G")
    //    Maximum amount of memory a script may consume. Processing large files
    //    (especially if doing image manipulation, etc., though not done here)
    //    can require significant memory.
    //
    // 4. `max_execution_time`: (e.g., "300", "600")
    //    Maximum time in seconds a script is allowed to run. Large file uploads
    //    and processing take time.
    //
    // 5. `max_input_time`: (e.g., "300", "600")
    //    Maximum time in seconds a script is allowed to parse input data, like
    //    file uploads.
    //
    // After changing `php.ini`, you typically need to restart your web server (Apache, Nginx, etc.)
    // or PHP-FPM service for the changes to take effect.
    // **************************************************************************************

    if ($_FILES['DocumentFile']['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => "File exceeds 'upload_max_filesize' directive in php.ini. Please contact server admin if larger files are needed.",
            UPLOAD_ERR_FORM_SIZE  => "File exceeds MAX_FILE_SIZE directive specified in the HTML form (if used).", // This script doesn't use MAX_FILE_SIZE in form.
            UPLOAD_ERR_PARTIAL    => "The uploaded file was only partially uploaded. Check network connection and try again.",
            UPLOAD_ERR_NO_FILE    => "No file was uploaded. Please select a file.",
            UPLOAD_ERR_NO_TMP_DIR => "Server configuration error: Missing a temporary folder for PHP uploads. Contact server admin.",
            UPLOAD_ERR_CANT_WRITE => "Server error: Failed to write file to temporary disk storage. Check permissions or disk space. Contact server admin.",
            UPLOAD_ERR_EXTENSION  => "A PHP extension stopped the file upload. Contact server admin.",
        ];
        $errorCode = $_FILES['DocumentFile']['error'];
        $response['message'] = $uploadErrors[$errorCode] ?? 'An unknown upload error occurred. Code: ' . $errorCode;
        error_log("Upload error (index.php): " . $response['message'] . " (File: " . ($_FILES['DocumentFile']['name'] ?? 'N/A') . ", Code: " . $errorCode . ")");
        echo json_encode($response);
        exit;
    }

    $username = $_POST['username'] ?? 'unknown_user';
    $DocumentDate = $_POST['DocumentDate'] ?? 'N/A';

    $sanitizedUsername = preg_replace('/[^a-zA-Z0-9_-]/', '', trim($username));
    if (empty($sanitizedUsername)) { $sanitizedUsername = 'default_user'; }

    $baseUploadDirWithSlash = rtrim($serverFileSystemBaseUploadDir, '/') . '/';

    if (!is_dir($baseUploadDirWithSlash)) {
        if (!mkdir($baseUploadDirWithSlash, 0775, true)) {
            $mkdir_error = error_get_last();
            $response['message'] = 'Critical Server Error: Failed to create base upload directory: ' . htmlspecialchars($baseUploadDirWithSlash) . '. Please check server permissions. The parent directory might not be writable by the web server.';
            error_log('CRITICAL: mkdir base directory failed (index.php): Path: ' . $baseUploadDirWithSlash . ' - PHP Error: ' . ($mkdir_error['message'] ?? 'Unknown error'));
            echo json_encode($response); exit;
        }
    } elseif (!is_writable($baseUploadDirWithSlash)) {
         $response['message'] = 'Critical Server Error: The base upload directory (' . htmlspecialchars($baseUploadDirWithSlash) . ') is not writable by the server. Check permissions.';
         error_log('CRITICAL: Base upload directory not writable (index.php): ' . $baseUploadDirWithSlash);
         echo json_encode($response); exit;
    }

    $userSpecificDirServer = $baseUploadDirWithSlash . $sanitizedUsername . '/';
    if (!is_dir($userSpecificDirServer)) {
        if (!mkdir($userSpecificDirServer, 0775, true)) { // Create user-specific directory recursively
            $mkdir_user_error = error_get_last();
            $response['message'] = 'Server Error: Failed to create user-specific directory: ' . htmlspecialchars($userSpecificDirServer) . '. Please check base directory permissions.';
            error_log('mkdir user directory failed (index.php): Path: ' . $userSpecificDirServer. ' - PHP Error: ' . ($mkdir_user_error['message'] ?? 'Unknown error'));
            echo json_encode($response); exit;
        }
    }
    if (!is_writable($userSpecificDirServer)) {
        $response['message'] = 'Server Error: The user upload directory (' . htmlspecialchars($userSpecificDirServer) . ') is not writable by the server. Check permissions.';
        error_log('User directory not writable (index.php): ' . $userSpecificDirServer);
        echo json_encode($response); exit;
    }

    $fileTmpPath = $_FILES['DocumentFile']['tmp_name'];
    $originalFileName = $_FILES['DocumentFile']['name'];
    $fileSize = $_FILES['DocumentFile']['size']; // Still useful for logging or other non-limiting logic if needed
    $fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));

    $safeOriginalFileName = preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($originalFileName));
    // Using true for more_entropy for better uniqueness
    $uniqueFilePrefix = uniqid($sanitizedUsername . '_', true);
    $serverGeneratedFileName = $uniqueFilePrefix . '.' . $fileExtension;

    $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'pdf']; // Define your allowed extensions
    if (!in_array($fileExtension, $allowedfileExtensions)) {
        $response['message'] = 'Invalid file extension detected: .' . htmlspecialchars($fileExtension) . '. Allowed types: JPG, JPEG, PNG, PDF.';
        error_log('Invalid file extension (index.php): ' . $fileExtension . ' for file ' . $originalFileName);
        echo json_encode($response); exit;
    }

    // MIME type check for added security
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $actualMimeType = finfo_file($finfo, $fileTmpPath);
    finfo_close($finfo);
    $allowedMimeTypes = [
        'image/jpeg', // for .jpg, .jpeg
        'image/png',  // for .png
        'application/pdf' // for .pdf
    ];
    if (!in_array($actualMimeType, $allowedMimeTypes)) {
         $response['message'] = 'Invalid file content type detected (' . htmlspecialchars($actualMimeType) . '). Allowed types: JPG (image/jpeg), PNG (image/png), PDF (application/pdf). Your file extension might not match its actual content.';
         error_log('Invalid MIME type (index.php): ' . $actualMimeType . ' for file ' . $originalFileName . ' (extension: ' . $fileExtension . ')');
         echo json_encode($response); exit;
    }

    /*
    // Script-level file size limit - REMOVED as per request.
    // Server-level limits in php.ini (upload_max_filesize, post_max_size) will still apply.
    if ($fileSize >= 5 * 1024 * 1024) { // Example: 5MB limit
        $response['message'] = 'File is too large. Maximum allowed size by this script is 5MB. (Note: Server may have other limits)';
        error_log('File size exceeded script limit (index.php): ' . $originalFileName . ' Size: ' . $fileSize);
        echo json_encode($response); exit;
    }
    */

    $destinationPath = $userSpecificDirServer . $serverGeneratedFileName;
    if (move_uploaded_file($fileTmpPath, $destinationPath)) {
        @chmod($destinationPath, 0644); // Set reasonable permissions for the uploaded file

        $response['success'] = true;
        $response['message'] = 'File uploaded successfully!';
        $webBasePathWithSlash = $webAccessibleBaseUploadPath ? rtrim($webAccessibleBaseUploadPath, '/') . '/' : '';
        $response['filePath'] = $webBasePathWithSlash . $sanitizedUsername . '/' . $serverGeneratedFileName;
        $response['originalFileName'] = $safeOriginalFileName;
        $response['serverFileName'] = $serverGeneratedFileName;
        $response['initialStatus'] = 'Pending'; // Default status after upload

        // Create a .txt file with Document details
        $txtFileName = $uniqueFilePrefix . '.txt'; // Use the same unique prefix
        $txtFilePath = $userSpecificDirServer . $txtFileName;

        $txtContent  = "Username: " . htmlspecialchars($username) . "\n";
        $txtContent .= "Date on Document: " . htmlspecialchars($DocumentDate) . "\n";
        $txtContent .= "Original Uploaded File: " . htmlspecialchars($originalFileName) . "\n";
        $txtContent .= "Saved File Name: " . htmlspecialchars($serverGeneratedFileName) . "\n";
        $txtContent .= "Upload Timestamp: " . date("Y-m-d H:i:s") . "\n";
        $txtContent .= "File Size (bytes): " . $fileSize . "\n";
        $txtContent .= "Status: Pending\n"; // Initial status

        if (file_put_contents($txtFilePath, $txtContent) === false) {
            $response['message'] .= " (Warning: Could not save details TXT file due to a server error. The main file was uploaded.)";
            error_log('file_put_contents failed for TXT file (index.php): ' . $txtFilePath . ' for uploaded file ' . $destinationPath);
        } else {
            @chmod($txtFilePath, 0644); // Set reasonable permissions for the TXT file
        }
    } else {
        $response['message'] = 'Server error: Could not save the uploaded file after it was received. Check destination path permissions and disk space.';
        $php_err = error_get_last();
        if($php_err) { $response['message'] .= " Specific error: " . $php_err['message']; }
        error_log('move_uploaded_file failed (index.php)! Temp: ' . $fileTmpPath . ' Dest: ' . $destinationPath . ($php_err ? " PHP Error: " . $php_err['message'] : " (No specific PHP error reported by error_get_last, check disk space/permissions/PHP logs)"));
    }

    $json_upload_output = json_encode($response);
    if ($json_upload_output === false) {
        $json_error_code = json_last_error();
        $json_error_msg = json_last_error_msg();
        error_log("Upload action (index.php): CRITICAL - json_encode failed. Error Code: " . $json_error_code . ", Message: " . $json_error_msg);
        // Fallback to a generic error if JSON encoding fails for the main response
        echo json_encode(['success' => false, 'message' => 'Server error: Could not encode JSON response for upload. JSON Error: ' . $json_error_msg]);
    } else {
        echo $json_upload_output;
    }
    exit;
}
// --- End PHP Action Handlers ---

// If the script reaches here, it means it's a regular GET request or an unhandled POST/GET action,
// so we render the HTML page.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Upload System - Professional</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Your existing CSS from index.php - unchanged */
         * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #2D2D2D;
            color: #E0E0E0;
            line-height: 1.6;
            padding: 15px;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #3C3C3C;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }
        @media (min-width: 768px) { .container { padding: 30px; } }
        h1, h2 { text-align: center; color: #E8E8E8; margin-bottom: 25px; }
        h1 { font-size: 1.8em; }
        h2 { text-align: left; font-size: 1.4em; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #505050; padding-bottom: 10px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #C8C8C8; font-size: 0.95em; }
        .upload-section { margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        input[type="text"], input[type="date"], select { width: 100%; padding: 12px 15px; background-color: #4A4A4A; color: #E0E0E0; border: 1px solid #606060; border-radius: 6px; font-size: 1em; transition: border-color 0.2s ease, box-shadow 0.2s ease; }
        input[type="text"]:focus, input[type="date"]:focus, select:focus { border-color: #007BFF; box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25); outline: none; }
        input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(0.8); cursor: pointer; }
        input::placeholder, select::placeholder { color: #8A8A8A; opacity: 1; }
        /* select { appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23C8C8C8'%3E%3Cpath fill-rule='evenodd' d='M8 11.293l-4.146-4.147a.75.75 0 011.06-1.06L8 9.172l3.086-3.086a.75.75 0 111.061 1.06L8 11.293z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 15px center; background-size: 16px; padding-right: 40px; } */
        .file-upload { display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 30px; border: 2px dashed #007BFF; border-radius: 8px; background-color: #454545; color: #B0B0B0; cursor: pointer; transition: all 0.3s; min-height: 150px; }
        .file-upload:hover { background-color: #4F4F4F; border-color: #0056b3;}
        .file-upload.highlight { background-color: #405266; border-color: #0056b3; }
        .file-upload input { display: none; }
        .upload-icon { font-size: 3em; color: #007BFF; margin-bottom: 15px; }
        .file-upload p { font-size: 0.95em; }
        .btn { position: relative; display: inline-flex; align-items: center; justify-content: center; gap: 8px; background-color: #007BFF; color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-size: 1em; font-weight: 600; transition: background-color 0.2s ease, transform 0.1s ease; width: 100%; text-align: center; text-decoration: none; line-height: 1.2; }
        .btn i { font-size: 1em; }
        .btn:hover { background-color: #0056b3; transform: translateY(-1px); }
        .btn:active { transform: translateY(0px); }
        .btn:disabled { background-color: #555; color: #888; cursor: not-allowed; transform: none; opacity: 0.7;}
        .btn.btn--loading .btn-text { visibility: hidden; opacity: 0; }
        .btn.btn--loading::after { content: ""; position: absolute; width: 20px; height: 20px; top: 50%; left: 50%; transform: translate(-50%, -50%); border: 3px solid rgba(255, 255, 255, 0.3); border-top-color: #ffffff; border-radius: 50%; animation: btn-spinner 0.8s linear infinite; }
        @keyframes btn-spinner { from { transform: translate(-50%, -50%) rotate(0deg); } to { transform: translate(-50%, -50%) rotate(360deg); } }
        #refresh-statuses-btn { width: auto; padding: 8px 15px !important; font-size: 0.9em !important; background-color: #606060; }
        #refresh-statuses-btn:hover { background-color: #505050;}
        #refresh-statuses-btn:disabled { background-color: #484848; color: #777;}
        #refresh-statuses-btn i.fa-spin { margin-right: 6px; }
        .file-list { margin-top: 15px; }
        .file-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 15px; background-color: #484848; border-radius: 6px; margin-bottom: 10px; font-size: 0.9em; }
        .file-name { flex-grow: 1; margin-right: 10px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .file-remove { color: #FF6B6B; cursor: pointer; font-weight: bold; font-size: 1.2em; transition: color 0.2s ease; }
        .file-remove:hover { color: #ff4747; }
        .saved-Documents { margin-top: 40px; padding-top: 20px; border-top: 1px solid #505050; }
        .saved-Document-item { display: flex; flex-direction: column; padding: 15px; background-color: #454545; border-left-width: 6px; border-left-style: solid; border-radius: 6px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .saved-Document-item.status-pending { border-left-color: #ffc107; }
        .saved-Document-item.status-accepted { border-left-color: #28a745; }
        .saved-Document-item.status-rejected { border-left-color: #dc3545; }
        .saved-Document-item.status-error, .saved-Document-item.status-not-found, .saved-Document-item.status-path-error, .saved-Document-item.status-security-error, .saved-Document-item.status-unknown, .saved-Document-item.status-server-config-error, .saved-Document-item.status-status-unavailable { border-left-color: #6c757d; }
        .Document-info { flex-grow: 1; margin-bottom:15px; }
        .Document-info h3 { color: #E8E8E8; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; font-size: 1.15em; flex-wrap: wrap; }
        .Document-info p { margin-bottom: 5px; font-size: 0.9em; color: #B8B8B8;}
        .Document-info strong { color: #D8D8D8; margin-right: 5px; }
        .Document-date-info { color: #9A9A9A; font-size: 0.8em; margin-top: 10px;}
        .Document-status { font-weight: 600; padding: 4px 10px; border-radius: 15px; font-size: 0.75em; margin-left: 10px; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; vertical-align: middle; white-space: nowrap; }
        .Document-status-pending { color: #2D2D2D; background-color: #ffc107; }
        .Document-status-accepted { color: white; background-color: #28a745; }
        .Document-status-rejected { color: white; background-color: #dc3545; }
        .Document-status-error, .Document-status-not-found, .Document-status-path-error, .Document-status-security-error, .Document-status-unknown, .Document-status-server-config-error, .Document-status-status-unavailable { color: #E0E0E0; background-color: #606060; }
        .Document-actions { display: flex; justify-content: flex-end; align-items: center; width: 100%; margin-top: 10px; gap: 10px; }
        .Document-actions button { background-color: #555555; color: #E0E0E0; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-size: 0.85em; font-weight: 500; display:inline-flex; align-items:center; gap: 6px; transition: background-color 0.2s ease; }
        .Document-actions button:hover { background-color: #656565; }
        .Document-actions button i { font-size: 0.9em; }
        .Document-actions button:disabled { background-color: #404040; color: #777; cursor: not-allowed;}
        .notification { padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; opacity: 0; transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out; visibility: hidden; color: #FFFFFF; box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
        .notification.visible { opacity: 1; visibility: visible; }
        .notification.success { background-color: #28a745; border-left: 5px solid #1e7e34; }
        .notification.error { background-color: #dc3545; border-left: 5px solid #b02a37; }
        @media (max-width: 600px) {
            body { padding: 10px;} .container { margin: 10px auto; padding: 15px; } h1 { font-size: 1.5em;} h2 { font-size: 1.2em;}
            .saved-Document-item { padding: 12px; } .Document-info h3 { font-size: 1.05em; align-items: flex-start; }
            .Document-status { margin-left: 0; margin-top: 5px; } .Document-actions { flex-direction: column; align-items: stretch; gap: 8px; }
            .Document-actions button { width: 100%; margin-left: 0; justify-content: center; } .Document-actions button:last-child { margin-bottom: 0; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Document Upload</h1>
        <div id="notification" class="notification"></div>
        <div class="upload-section">
            <div class="form-group"> <label for="username">Username</label> <input type="text" id="username" placeholder="Enter your username"> </div>
            <div class="form-group"> <label for="Document-date">Date on Document</label> <input type="date" id="Document-date"> </div>
            <div class="form-group">
                <label>Upload Document Image/PDF</label>
                <div id="file-upload-area" class="file-upload">
                    <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                    <p>Drag & drop files here or click to browse</p>
                    <input type="file" id="file-input" accept=".jpg,.jpeg,.png,.pdf">
                </div>
                <div id="file-list" class="file-list"></div>
            </div>
            <button id="save-button" class="btn" disabled>
                <span class="btn-text"><i class="fas fa-save"></i> Save Document</span>
            </button>
        </div>
        <div class="saved-Documents">
            <h2>Saved Documents <button id="refresh-statuses-btn" title="Refresh statuses from server" class="btn"><i class="fas fa-sync-alt"></i> Refresh</button></h2>
            <div id="saved-Documents-list"><p style="color: #A0A0A0; text-align:center; padding: 20px 0;">No saved Documents found.</p></div>
        </div>
    </div>
    <script>
        const fileUploadArea = document.getElementById('file-upload-area');
        const fileInput = document.getElementById('file-input');
        const fileList = document.getElementById('file-list');
        const usernameInput = document.getElementById('username');
        const DocumentDateInput = document.getElementById('Document-date');
        const saveButton = document.getElementById('save-button');
        const savedDocumentsList = document.getElementById('saved-Documents-list');
        const notification = document.getElementById('notification');
        const refreshStatusesBtn = document.getElementById('refresh-statuses-btn');

        const today = new Date();
        DocumentDateInput.value = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

        let currentFile = null;
        let savedDocuments = JSON.parse(localStorage.getItem('bankDocumentsWithStatusV1')) || [];

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => fileUploadArea.addEventListener(eventName, preventDefaults, false));
        function preventDefaults(e) { e.preventDefault(); e.stopPropagation(); }
        ['dragenter', 'dragover'].forEach(eventName => fileUploadArea.addEventListener(eventName, () => fileUploadArea.classList.add('highlight'), false));
        ['dragleave', 'drop'].forEach(eventName => fileUploadArea.addEventListener(eventName, () => fileUploadArea.classList.remove('highlight'), false));
        fileUploadArea.addEventListener('drop', (e) => { if (e.dataTransfer.files.length > 0) handleFiles(e.dataTransfer.files[0]); }, false);
        fileUploadArea.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', function() { if (this.files.length > 0) handleFiles(this.files[0]); });
        
        function handleFiles(file) {
            currentFile = null;
            if (validateFile(file)) {
                currentFile = file;
                displayFile(file);
            } else {
                fileInput.value = ''; // Clear the input if validation fails
                fileList.innerHTML = '';
            }
            checkFormValidity();
        }

        function validateFile(file) {
            const types = ['image/jpeg', 'image/png', 'application/pdf'];
            // const maxSize = 5 * 1024 * 1024; // 5MB - Client-side limit removed as per request. Server-side limits (PHP.ini) are now the primary control.

            if (!types.includes(file.type)) {
                showNotification('Invalid file type. Allowed: JPG, PNG, PDF. Detected: ' + (file.type || 'unknown'), 'error');
                return false;
            }
            /*
            // Client-side file size validation removed as per request.
            // Server (PHP.ini) will enforce actual file size limits.
            if (file.size > maxSize) {
                showNotification('File is too large (Max 5MB according to previous client check). Server will validate actual limit.', 'error');
                return false;
            }
            */
            // Add a general warning if you want, or let server handle it.
            // console.log("Client-side file validation passed. Server will perform final checks including size.");
            return true;
        }

        function displayFile(file) {
            fileList.innerHTML = ''; // Clear previous file
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            const fileName = document.createElement('div');
            fileName.className = 'file-name';
            fileName.textContent = file.name + ` (${(file.size / 1024 / 1024).toFixed(2)} MB)`; // Show size
            const removeButton = document.createElement('div');
            removeButton.className = 'file-remove';
            removeButton.innerHTML = '<i class="fas fa-times"></i>';
            removeButton.title = 'Remove file';
            removeButton.onclick = (e) => {
                e.stopPropagation(); // Prevent triggering fileUploadArea click
                fileList.innerHTML = '';
                currentFile = null;
                fileInput.value = ''; // Important to clear the file input
                checkFormValidity();
            };
            fileItem.appendChild(fileName);
            fileItem.appendChild(removeButton);
            fileList.appendChild(fileItem);
        }

        function checkFormValidity() {
            if (!saveButton.classList.contains('btn--loading')) {
                saveButton.disabled = !(
                    usernameInput.value.trim() &&
                    DocumentDateInput.value &&
                    currentFile
                );
            }
        }
        [usernameInput, DocumentDateInput].forEach(i => {
            i.addEventListener('input', checkFormValidity);
            i.addEventListener('change', checkFormValidity);
        });

        saveButton.addEventListener('click', async function saveDocumentToServer() {
            if (!currentFile || (saveButton.disabled && !saveButton.classList.contains('btn--loading'))) {
                 showNotification('Please ensure username, Document date are filled and a file is selected.', 'error'); return;
            }
            // Re-validate before upload, especially if user could have changed the file via dev tools (unlikely but good practice)
            if (!validateFile(currentFile)) {
                fileInput.value = ''; fileList.innerHTML = ''; currentFile = null; checkFormValidity();
                return;
            }

            saveButton.disabled = true;
            saveButton.classList.add('btn--loading');

            const formData = new FormData();
            formData.append('username', usernameInput.value.trim());
            formData.append('DocumentDate', DocumentDateInput.value);
            formData.append('DocumentFile', currentFile);

            try {
                const response = await fetch(window.location.pathname, { // Assumes PHP is in the same file
                    method: 'POST',
                    body: formData
                });

                let result;
                const responseText = await response.text(); // Get text first to handle non-JSON errors

                if (!response.ok) {
                    let serverMessage = `Server error: ${response.status} ${response.statusText}`;
                    try {
                        const errorJson = JSON.parse(responseText);
                        if (errorJson && errorJson.message) {
                            serverMessage = errorJson.message; // Use server's specific error message
                        }
                    } catch (e) {
                        // Not a JSON error response from our script, probably a generic server error page (HTML, etc.)
                        serverMessage = `Upload failed. Server returned ${response.status}. Please check server logs. Response preview: ${responseText.substring(0, 200)}`;
                    }
                    throw new Error(serverMessage);
                }

                try {
                    result = JSON.parse(responseText);
                } catch (e) {
                    console.error("Failed to parse JSON response from server:", e, "Raw response:", responseText);
                    throw new Error("Invalid JSON response from server after successful upload status. Response: " + responseText.substring(0,200));
                }


                if (result.success) {
                    const Document = {
                        id: Date.now().toString() + Math.random().toString(36).substring(2,7), // More robust ID
                        username: usernameInput.value.trim(),
                        date: DocumentDateInput.value,
                        originalFileName: result.originalFileName || currentFile.name,
                        serverFileName: result.serverFileName,
                        filePath: result.filePath,
                        status: result.initialStatus || 'Pending',
                        uploadDate: new Date().toISOString()
                    };
                    savedDocuments.push(Document);
                    localStorage.setItem('bankDocumentsWithStatusV1', JSON.stringify(savedDocuments));

                    // Reset form
                    usernameInput.value = '';
                    const today = new Date();
                    DocumentDateInput.value = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
                    fileList.innerHTML = ''; currentFile = null; fileInput.value = '';

                    showNotification('Document saved! Status: ' + htmlspecialchars(Document.status), 'success');
                    renderSavedDocuments();
                } else {
                    // Server responded with success:false, result.message should contain the reason
                    showNotification(htmlspecialchars(result.message) || 'Error saving Document. Please check details and try again.', 'error');
                }
            } catch (error) {
                console.error('Upload/Save error:', error);
                // Error.message is already likely HTML-escaped if it came from our htmlspecialchars function or is a generic browser message.
                // If it's from the server (JSON.message), it also should be safe or escaped there.
                showNotification(`Operation failed: ${error.message}. Check console/server logs.`, 'error');
            } finally {
                saveButton.classList.remove('btn--loading');
                checkFormValidity(); // Re-enable button if criteria met (though form is cleared)
            }
        });

        let notificationTimeout;
        function showNotification(message, type) {
            clearTimeout(notificationTimeout);
            // Assuming message is already safe or will be escaped by server if from there.
            // For client-generated messages, ensure they are safe if they include dynamic parts not already escaped.
            notification.textContent = message;
            notification.className = `notification ${type} visible`;
            notificationTimeout = setTimeout(() => { notification.classList.remove('visible'); }, 5000);
        }

        async function refreshDocumentStatuses() {
            if (savedDocuments.length === 0) { renderSavedDocuments(); return; } // No need to call server
            refreshStatusesBtn.disabled = true;
            refreshStatusesBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';

            const pathsToQuery = savedDocuments.map(Document => Document.filePath).filter(p => typeof p === 'string' && p.trim() !== '');
            if (pathsToQuery.length === 0) {
                 refreshStatusesBtn.disabled = false;
                 refreshStatusesBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
                 // Potentially render to clear out any old views if all paths became invalid, though renderSavedDocuments() at finally block handles this.
                 renderSavedDocuments();
                 return;
            }

            try {
                // Construct the URL correctly, assuming index.php is the current page or accessible at this path.
                const fetchURL = new URL(window.location.pathname, window.location.origin);
                fetchURL.searchParams.set('action', 'get_Document_statuses');

                const response = await fetch(fetchURL.toString(), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ filePaths: pathsToQuery })
                });

                const responseText = await response.text(); // Get text first

                if (!response.ok) {
                     console.error("Server error raw response (refreshDocumentStatuses):", responseText.substring(0, 500));
                     let serverMessage = `Server error fetching statuses: ${response.status} ${response.statusText}.`;
                     try {
                        const errorJson = JSON.parse(responseText);
                        if(errorJson && errorJson.error) serverMessage = errorJson.error + (errorJson.details ? ` (${errorJson.details})` : '');
                         else if(errorJson && errorJson.message) serverMessage = errorJson.message;
                     } catch(e) { /* ignore if not json, use limited raw text */ serverMessage = `Server error fetching statuses: ${response.status}. ${responseText.substring(0,100)}` }
                     throw new Error(serverMessage);
                }
                
                const serverResponse = await JSON.parse(responseText); // Now parse, should be safe if response.ok

                if (serverResponse.error) { // Server itself reported an error in JSON format
                    throw new Error(`Server returned an error: ${htmlspecialchars(serverResponse.error)} ${htmlspecialchars(serverResponse.details || '')}`);
                }

                const newStatuses = serverResponse; // This is the statuses object { filePath: status, ... }
                let updated = false;
                savedDocuments.forEach(Document => {
                    if (Document.filePath) {
                        if (newStatuses.hasOwnProperty(Document.filePath)) { // Check if server provided status for this path
                            if (Document.status !== newStatuses[Document.filePath]) {
                                Document.status = newStatuses[Document.filePath];
                                updated = true;
                            }
                        } else if (Document.status !== 'Status Unavailable' && Document.status !== 'Not Found') {
                            // If server did NOT return a status for a known filePath, it implies an issue or the file is no longer tracked by server logic for statuses.
                            console.warn(`Status for Document path '${Document.filePath}' not found in server response. Current status: ${Document.status}. Marking as 'Status Unavailable'.`);
                            Document.status = 'Status Unavailable'; // Or another appropriate status like 'Not Found' or 'Error'
                            updated = true;
                        }
                    }
                });

                if (updated) {
                    localStorage.setItem('bankDocumentsWithStatusV1', JSON.stringify(savedDocuments));
                    showNotification('Document statuses updated from server.', 'success');
                } else {
                    showNotification('Statuses are up to date or no applicable changes found.', 'success');
                }
            } catch (error) {
                console.error("Error refreshing statuses:", error);
                showNotification('Could not refresh statuses: ' + (error.message || 'Unknown error'), 'error');
            } finally {
                 refreshStatusesBtn.disabled = false;
                 refreshStatusesBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
                 renderSavedDocuments(); // Always re-render to reflect any changes or current state
            }
        }

        refreshStatusesBtn.addEventListener('click', refreshDocumentStatuses);

        function renderSavedDocuments() {
            savedDocumentsList.innerHTML = '';
            if (savedDocuments.length === 0) {
                savedDocumentsList.innerHTML = '<p style="color: #A0A0A0; text-align:center; padding: 20px 0;">No saved Documents found. Upload one to get started!</p>';
                return;
            }

            // Sort by uploadDate, newest first
            const sortedDocuments = [...savedDocuments].sort((a, b) => new Date(b.uploadDate) - new Date(a.uploadDate));

            sortedDocuments.forEach(Document => {
                const DocumentItem = document.createElement('div');
                const status = Document.status || 'Unknown'; // Default to 'Unknown' if status is missing
                // Sanitize status for CSS class: lowercase, replace spaces/colons with hyphens, remove other special chars
                const statusSanitized = status.toLowerCase()
                                          .replace(/[\s:]+/g, '-') // Replace spaces and colons with a single hyphen
                                          .replace(/[^a-z0-9-]/g, ''); // Remove any character not a letter, number, or hyphen
                const statusClass = 'status-' + statusSanitized;

                DocumentItem.className = `saved-Document-item ${statusClass}`;

                const DocumentInfo = document.createElement('div');
                DocumentInfo.className = 'Document-info';

                // Status display with sanitized class for styling
                const statusDisplay = `<span class="Document-status Document-status-${statusSanitized}">${htmlspecialchars(status)}</span>`;

                DocumentInfo.innerHTML = `
                    <h3>File: ${htmlspecialchars(Document.originalFileName || 'N/A')} ${statusDisplay}</h3>
                    <p><strong>Username:</strong> ${htmlspecialchars(Document.username || 'N/A')}</p>
                    <p class="Document-date-info">
                        <strong>Document Date:</strong> ${formatDate(Document.date)} |
                        <strong>Uploaded:</strong> ${formatDate(Document.uploadDate, true)}
                    </p>
                    ${Document.serverFileName ? `<p style="font-size:0.8em; color:#888;">Server Filename: ${htmlspecialchars(Document.serverFileName)}</p>` : ''}
                `;

                const DocumentActions = document.createElement('div');
                DocumentActions.className = 'Document-actions';

                const viewButton = document.createElement('button');
                viewButton.innerHTML = '<i class="fas fa-eye"></i> View';
                viewButton.title = 'View File (opens in new tab)';
                if (Document.filePath && (status !== 'Not Found' && status !== 'Path Error: Structure' && status !== 'Security Error: Path' && status !== 'Server Config Error' && status !== 'Status Unavailable')) {
                    // Construct full URL for viewing/downloading
                    const fileUrl = new URL(Document.filePath, window.location.origin).href;
                    viewButton.addEventListener('click', () => window.open(fileUrl, '_blank'));
                } else {
                    viewButton.disabled = true; viewButton.style.opacity = "0.5";
                    viewButton.title = 'File path not available or file not found on server.';
                }

                const downloadButton = document.createElement('button');
                downloadButton.innerHTML = '<i class="fas fa-download"></i> Download';
                downloadButton.title = 'Download File';
                 if (Document.filePath && (status !== 'Not Found' && status !== 'Path Error: Structure' && status !== 'Security Error: Path' && status !== 'Server Config Error' && status !== 'Status Unavailable')) {
                    const fileUrl = new URL(Document.filePath, window.location.origin).href;
                    downloadButton.addEventListener('click', () => {
                        const link = document.createElement('a');
                        link.href = fileUrl;
                        link.download = Document.originalFileName || Document.serverFileName || 'download';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    });
                } else {
                    downloadButton.disabled = true; downloadButton.style.opacity = "0.5";
                    downloadButton.title = 'File path not available or file not found on server for download.';
                }

                const deleteButton = document.createElement('button');
                deleteButton.innerHTML = '<i class="fas fa-trash-alt"></i> Delete (Local)';
                deleteButton.title = 'Delete this Document entry from your local browser list. This will not delete it from the server.';
                deleteButton.addEventListener('click', () => {
                    if (confirm(`Are you sure you want to remove "${htmlspecialchars(Document.originalFileName)}" from this local list? This action does not delete the file from the server.`)){
                        savedDocuments = savedDocuments.filter(s => s.id !== Document.id);
                        localStorage.setItem('bankDocumentsWithStatusV1', JSON.stringify(savedDocuments));
                        renderSavedDocuments(); // Re-render the list
                        showNotification('Document removed from local list.', 'success');
                    }
                });

                DocumentActions.appendChild(viewButton);
                DocumentActions.appendChild(downloadButton);
                DocumentActions.appendChild(deleteButton);

                DocumentItem.appendChild(DocumentInfo);
                DocumentItem.appendChild(DocumentActions);
                savedDocumentsList.appendChild(DocumentItem);
            });
        }

        function formatDate(dateString, includeTime = false) {
            if (!dateString) return "N/A";
            try {
                const date = new Date(dateString);
                if (isNaN(date.getTime())) return "Invalid Date"; // Check for invalid date
                const options = { year: 'numeric', month: 'short', day: 'numeric' };
                if (includeTime) {
                    options.hour = '2-digit';
                    options.minute = '2-digit';
                    // options.second = '2-digit'; // Optional: include seconds
                }
                return date.toLocaleDateString(undefined, options);
            } catch (e) {
                console.warn("Error formatting date:", dateString, e);
                return dateString; // Fallback to original string if formatting fails
            }
        }

        function htmlspecialchars(str) {
            if (typeof str !== 'string') {
                if (str === null || str === undefined) return '';
                try { str = String(str); } catch (e) { return ''; }
            }
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return str.replace(/[&<>"']/g, (m) => map[m]);
        }

        // Initial setup
        renderSavedDocuments();
        checkFormValidity();
        if(savedDocuments.length > 0) {
            refreshDocumentStatuses(); // Refresh statuses on page load if there are existing documents
        }
    </script>
</body>
</html>

