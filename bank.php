<?php
// At the VERY TOP of index.php for debugging:
// For production, ensure these are OFF or logging to a file only.
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Define paths
// IMPORTANT: The path /Documents/ is an ABSOLUTE filesystem path.
// 1. The web server user (e.g., www-data) MUST have write permissions to /Documents/
//    and be able to create subdirectories within it.
// 2. Your web server (Apache/Nginx) MUST be configured to serve files from this
//    /Documents/ filesystem path if you want them to be web-accessible.
//    The $webAccessibleBaseUploadPath should correspond to the URL path.
//    For example, if you want URLs like http://yourdomain.com/Documents/user/file.pdf,
//    then your web server must map the /Documents/ URL prefix to the /Documents/ filesystem directory.
$serverFileSystemBaseUploadDir = '/Documents/';
$webAccessibleBaseUploadPath = 'Documents/';


// Function to parse TXT file for status, used only within get_slip_statuses action
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


// --- ACTION: get_slip_statuses ---
// Handles POST requests specifically for fetching slip statuses via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'get_slip_statuses') {

    ini_set('display_errors', 0);
    error_reporting(E_ALL);
    ini_set('log_errors', 1);
    // Optionally: ini_set('error_log', '/path/to/your/php-error.log');

    header('Content-Type: application/json; charset=utf-8');

    $responseStatuses = [];
    $rawInput = file_get_contents('php://input');
    $requestData = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("get_slip_statuses (index.php): Invalid JSON payload received. Error: " . json_last_error_msg() . ". Payload: " . $rawInput);
        echo json_encode(['error' => 'Invalid request format: Bad JSON.', 'details' => json_last_error_msg(), 'statuses' => []]);
        exit;
    }

    if (isset($requestData['filePaths']) && is_array($requestData['filePaths'])) {
        $realBaseDir = realpath($serverFileSystemBaseUploadDir);
        if (!$realBaseDir) {
            error_log("get_slip_statuses (index.php): CRITICAL - Base upload directory is invalid or does not exist: " . $serverFileSystemBaseUploadDir);
            foreach ($requestData['filePaths'] as $webFilePath) {
                 if (!empty($webFilePath) && is_string($webFilePath)) $responseStatuses[$webFilePath] = 'Server Config Error';
            }
        } else {
            foreach ($requestData['filePaths'] as $webFilePath) {
                if (empty($webFilePath) || !is_string($webFilePath)) {
                    error_log("get_slip_statuses (index.php): Received empty or non-string filePath in list.");
                    if (!empty($webFilePath) && is_scalar($webFilePath)) {
                        $responseStatuses[(string)$webFilePath] = 'Path Error: Invalid type';
                    }
                    continue;
                }

                if (strpos($webFilePath, $webAccessibleBaseUploadPath) === 0 && strpos($webFilePath, '..') === false) {
                    $relativePath = substr($webFilePath, strlen($webAccessibleBaseUploadPath));
                    $parts = explode('/', $relativePath, 2);

                    if (count($parts) === 2) {
                        $userFolder = preg_replace('/[^a-zA-Z0-9_-]/', '', $parts[0]);
                        $slipFileFullName = preg_replace('/[^a-zA-Z0-9_.-]/', '', $parts[1]);

                        if(empty($userFolder) || empty($slipFileFullName)){
                             $responseStatuses[$webFilePath] = 'Path Error: Invalid Chars';
                             error_log("get_slip_statuses (index.php): Invalid characters in path components derived from: " . $webFilePath);
                             continue;
                        }

                        $txtFileName = pathinfo($slipFileFullName, PATHINFO_FILENAME) . '.txt';
                        $txtFilePathOnServer = rtrim($serverFileSystemBaseUploadDir, '/') . '/' . $userFolder . '/' . $txtFileName; // Ensure single slash

                        $realTxtPath = realpath($txtFilePathOnServer);

                        if ($realTxtPath && strpos($realTxtPath, $realBaseDir) === 0 && file_exists($realTxtPath)) {
                             $responseStatuses[$webFilePath] = parseTxtFileForStatusInIndex($realTxtPath);
                        } else {
                            $responseStatuses[$webFilePath] = 'Not Found';
                            error_log("get_slip_statuses (index.php): TXT file check failed for client path '" . $webFilePath . "'. Constructed server path: '" . $txtFilePathOnServer . "'. Real TxtPath: '" . ($realTxtPath ?: 'false') . "'. File exists: ".(file_exists($txtFilePathOnServer) ? 'yes':'no'));
                        }
                    } else {
                         $responseStatuses[$webFilePath] = 'Path Error: Structure';
                         error_log("get_slip_statuses (index.php): Invalid path structure for webFilePath: " . $webFilePath);
                    }
                } else {
                    $responseStatuses[$webFilePath] = 'Security Error: Path';
                    error_log("get_slip_statuses (index.php): Security error or invalid base for webFilePath: '" . $webFilePath . "'. Expected base: '" . $webAccessibleBaseUploadPath . "'");
                }
            }
        }
    } else {
        error_log("get_slip_statuses (index.php): 'filePaths' key missing or not an array in JSON payload. Payload: " . $rawInput);
        echo json_encode(['error' => "Invalid request: 'filePaths' array not found or malformed.", 'statuses' => []]);
        exit;
    }

    $json_output = json_encode($responseStatuses);

    if ($json_output === false) {
        $json_error_code = json_last_error();
        $json_error_msg = json_last_error_msg();
        error_log("get_slip_statuses (index.php): CRITICAL - json_encode failed. Error Code: " . $json_error_code . ", Message: " . $json_error_msg . ". Data attempted: " . print_r($responseStatuses, true));

        echo json_encode([
            'error' => 'Server error: Could not encode JSON response for statuses.',
            'details' => 'Error Code ' . $json_error_code,
            'statuses' => []
        ]);
    } else {
        echo $json_output;
    }
    exit;
}


// --- ACTION: uploadFile (Main Upload Handler) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['slipFile']) && !isset($_GET['action'])) {
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
    ini_set('log_errors', 1);

    header('Content-Type: application/json; charset=utf-8');
    $response = ['success' => false, 'message' => 'Upload initialization error.'];

    if ($_FILES['slipFile']['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => "File exceeds upload_max_filesize in php.ini.",
            UPLOAD_ERR_FORM_SIZE  => "File exceeds MAX_FILE_SIZE in HTML form.",
            UPLOAD_ERR_PARTIAL    => "File was only partially uploaded. Check network.",
            UPLOAD_ERR_NO_FILE    => "No file was received by the server.",
            UPLOAD_ERR_NO_TMP_DIR => "Server configuration error: Missing PHP temporary folder.",
            UPLOAD_ERR_CANT_WRITE => "Server error: Failed to write file to temporary disk storage.",
            UPLOAD_ERR_EXTENSION  => "Server error: A PHP extension stopped the file upload.",
        ];
        $errorCode = $_FILES['slipFile']['error'];
        $response['message'] = $uploadErrors[$errorCode] ?? 'Unknown upload error occurred. Code: ' . $errorCode;
        error_log("Upload error (index.php): " . $response['message'] . " (Code: " . $errorCode . ")");
        echo json_encode($response);
        exit;
    }

    $username = $_POST['username'] ?? 'unknown_user';
    $slipDate = $_POST['slipDate'] ?? 'N/A';

    $sanitizedUsername = preg_replace('/[^a-zA-Z0-9_-]/', '', trim($username));
    if (empty($sanitizedUsername)) { $sanitizedUsername = 'default_user'; }

    // Ensure $serverFileSystemBaseUploadDir ends with a slash
    $baseUploadDirWithSlash = rtrim($serverFileSystemBaseUploadDir, '/') . '/';

    if (!is_dir($baseUploadDirWithSlash)) {
        // Attempt to create the base directory if it's an absolute path and doesn't exist.
        // This might fail if permissions are not set up for the web server user at the root level.
        if (!mkdir($baseUploadDirWithSlash, 0775, true)) {
            $response['message'] = 'Failed to create base upload directory: ' . $baseUploadDirWithSlash . '. Check server permissions and ensure path is correct.';
            error_log('mkdir base failed (index.php): ' . $baseUploadDirWithSlash . ' - Error: ' . (error_get_last()['message'] ?? 'Unknown error'));
            echo json_encode($response); exit;
        }
    } elseif (!is_writable($baseUploadDirWithSlash)) {
         $response['message'] = 'The base upload directory (' . $baseUploadDirWithSlash . ') is not writable by the server.';
         error_log('Base dir not writable (index.php): ' . $baseUploadDirWithSlash);
         echo json_encode($response); exit;
    }

    $userSpecificDirServer = $baseUploadDirWithSlash . $sanitizedUsername . '/';
    if (!is_dir($userSpecificDirServer)) {
        if (!mkdir($userSpecificDirServer, 0775, true)) {
            $response['message'] = 'Failed to create user directory: ' . $userSpecificDirServer . '. Check base directory permissions.';
            error_log('mkdir user failed (index.php): ' . $userSpecificDirServer. ' - Error: ' . (error_get_last()['message'] ?? 'Unknown error'));
            echo json_encode($response); exit;
        }
    }
    if (!is_writable($userSpecificDirServer)) {
        $response['message'] = 'The user upload directory (' . $userSpecificDirServer . ') is not writable by the server.';
        error_log('User dir not writable (index.php): ' . $userSpecificDirServer);
        echo json_encode($response); exit;
    }

    $fileTmpPath = $_FILES['slipFile']['tmp_name'];
    $originalFileName = $_FILES['slipFile']['name'];
    $fileSize = $_FILES['slipFile']['size'];
    $fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));

    $safeOriginalFileName = preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($originalFileName));
    $uniqueFilePrefix = uniqid($sanitizedUsername . '_', false);
    $serverGeneratedFileName = $uniqueFilePrefix . '.' . $fileExtension;

    $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
    if (!in_array($fileExtension, $allowedfileExtensions)) {
        $response['message'] = 'Invalid file extension. Allowed: JPG, JPEG, PNG, PDF.';
        echo json_encode($response); exit;
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $actualMimeType = finfo_file($finfo, $fileTmpPath);
    finfo_close($finfo);
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'application/pdf']; // image/jpg is not standard, use image/jpeg
    if (!in_array($actualMimeType, $allowedMimeTypes)) {
         $response['message'] = 'Invalid file type detected (' . $actualMimeType . '). Allowed: JPG (image/jpeg), PNG (image/png), PDF (application/pdf).';
         error_log('Invalid MIME type (index.php): ' . $actualMimeType . ' for file ' . $originalFileName);
         echo json_encode($response); exit;
    }
    if ($fileSize >= 5 * 1024 * 1024) {
        $response['message'] = 'File is too large on server. Maximum allowed size is 5MB.';
        echo json_encode($response); exit;
    }

    $destinationPath = $userSpecificDirServer . $serverGeneratedFileName;
    if (move_uploaded_file($fileTmpPath, $destinationPath)) {
        $response['success'] = true;
        $response['message'] = 'File uploaded successfully!';
        // Ensure $webAccessibleBaseUploadPath ends with a slash for URL construction
        $webBasePathWithSlash = rtrim($webAccessibleBaseUploadPath, '/') . '/';
        $response['filePath'] = $webBasePathWithSlash . $sanitizedUsername . '/' . $serverGeneratedFileName;
        $response['originalFileName'] = $safeOriginalFileName;
        $response['serverFileName'] = $serverGeneratedFileName;
        $response['initialStatus'] = 'Pending';

        $txtFileName = $uniqueFilePrefix . '.txt';
        $txtFilePath = $userSpecificDirServer . $txtFileName;

        $txtContent  = "Username: " . htmlspecialchars($username) . "\n";
        $txtContent .= "Date on Slip: " . htmlspecialchars($slipDate) . "\n";
        $txtContent .= "Original Uploaded File: " . htmlspecialchars($originalFileName) . "\n";
        $txtContent .= "Saved File Name: " . htmlspecialchars($serverGeneratedFileName) . "\n";
        $txtContent .= "Upload Timestamp: " . date("Y-m-d H:i:s") . "\n";
        $txtContent .= "Status: Pending\n";

        if (file_put_contents($txtFilePath, $txtContent) === false) {
            $response['message'] .= " (Warning: Could not save details TXT file)";
            error_log('file_put_contents failed for TXT file (index.php): ' . $txtFilePath);
        }
    } else {
        $response['message'] = 'Server error: Could not save uploaded file.';
        $php_err = error_get_last();
        if($php_err) { $response['message'] .= " Error details: " . $php_err['message']; }
        error_log('move_uploaded_file failed (index.php)! Temp: ' . $fileTmpPath . ' Dest: ' . $destinationPath . ($php_err ? " PHP Error: " . $php_err['message'] : ""));
    }

    $json_upload_output = json_encode($response);
    if ($json_upload_output === false) {
        $json_error_code = json_last_error();
        $json_error_msg = json_last_error_msg();
        error_log("Upload action (index.php): CRITICAL - json_encode failed. Error Code: " . $json_error_code . ", Message: " . $json_error_msg);
        echo json_encode(['success' => false, 'message' => 'Server error: Could not encode JSON response for upload.']);
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
    <title>Bank Slip Upload System - Professional</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Your existing CSS from index.php */
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
        .saved-slips { margin-top: 40px; padding-top: 20px; border-top: 1px solid #505050; }
        .saved-slip-item { display: flex; flex-direction: column; padding: 15px; background-color: #454545; border-left-width: 6px; border-left-style: solid; border-radius: 6px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .saved-slip-item.status-pending { border-left-color: #ffc107; }
        .saved-slip-item.status-accepted { border-left-color: #28a745; }
        .saved-slip-item.status-rejected { border-left-color: #dc3545; }
        .saved-slip-item.status-error, .saved-slip-item.status-not-found, .saved-slip-item.status-path-error, .saved-slip-item.status-security-error, .saved-slip-item.status-unknown, .saved-slip-item.status-server-config-error { border-left-color: #6c757d; }
        .slip-info { flex-grow: 1; margin-bottom:15px; }
        .slip-info h3 { color: #E8E8E8; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; font-size: 1.15em; flex-wrap: wrap; }
        .slip-info p { margin-bottom: 5px; font-size: 0.9em; color: #B8B8B8;}
        .slip-info strong { color: #D8D8D8; margin-right: 5px; }
        .slip-date-info { color: #9A9A9A; font-size: 0.8em; margin-top: 10px;}
        .slip-status { font-weight: 600; padding: 4px 10px; border-radius: 15px; font-size: 0.75em; margin-left: 10px; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; vertical-align: middle; white-space: nowrap; }
        .slip-status-pending { color: #2D2D2D; background-color: #ffc107; }
        .slip-status-accepted { color: white; background-color: #28a745; }
        .slip-status-rejected { color: white; background-color: #dc3545; }
        .slip-status-error, .slip-status-not-found, .slip-status-path-error, .slip-status-security-error, .slip-status-unknown, .slip-status-server-config-error { color: #E0E0E0; background-color: #606060; }
        .slip-actions { display: flex; justify-content: flex-end; align-items: center; width: 100%; margin-top: 10px; gap: 10px; }
        .slip-actions button { background-color: #555555; color: #E0E0E0; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-size: 0.85em; font-weight: 500; display:inline-flex; align-items:center; gap: 6px; transition: background-color 0.2s ease; }
        .slip-actions button:hover { background-color: #656565; }
        .slip-actions button i { font-size: 0.9em; }
        .slip-actions button:disabled { background-color: #404040; color: #777; cursor: not-allowed;}
        .notification { padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; opacity: 0; transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out; visibility: hidden; color: #FFFFFF; box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
        .notification.visible { opacity: 1; visibility: visible; }
        .notification.success { background-color: #28a745; border-left: 5px solid #1e7e34; }
        .notification.error { background-color: #dc3545; border-left: 5px solid #b02a37; }
        @media (max-width: 600px) {
            body { padding: 10px;} .container { margin: 10px auto; padding: 15px; } h1 { font-size: 1.5em;} h2 { font-size: 1.2em;}
            .saved-slip-item { padding: 12px; } .slip-info h3 { font-size: 1.05em; align-items: flex-start; }
            .slip-status { margin-left: 0; margin-top: 5px; } .slip-actions { flex-direction: column; align-items: stretch; gap: 8px; }
            .slip-actions button { width: 100%; margin-left: 0; justify-content: center; } .slip-actions button:last-child { margin-bottom: 0; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Bank Slip Upload</h1>
        <div id="notification" class="notification"></div>
        <div class="upload-section">
            <div class="form-group"> <label for="username">Username</label> <input type="text" id="username" placeholder="Enter your username"> </div>
            <div class="form-group"> <label for="slip-date">Date on Slip</label> <input type="date" id="slip-date"> </div>
            <div class="form-group">
                <label>Upload Slip Image/PDF</label>
                <div id="file-upload-area" class="file-upload">
                    <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                    <p>Drag & drop files here or click to browse</p>
                    <input type="file" id="file-input" accept=".jpg,.jpeg,.png,.pdf">
                </div>
                <div id="file-list" class="file-list"></div>
            </div>
            <button id="save-button" class="btn" disabled>
                <span class="btn-text"><i class="fas fa-save"></i> Save Bank Slip</span>
            </button>
        </div>
        <div class="saved-slips">
            <h2>Saved Bank Slips <button id="refresh-statuses-btn" title="Refresh statuses from server" class="btn"><i class="fas fa-sync-alt"></i> Refresh</button></h2>
            <div id="saved-slips-list"><p style="color: #A0A0A0; text-align:center; padding: 20px 0;">No saved bank slips found.</p></div>
        </div>
    </div>
    <script>
        const fileUploadArea = document.getElementById('file-upload-area');
        const fileInput = document.getElementById('file-input');
        const fileList = document.getElementById('file-list');
        const usernameInput = document.getElementById('username');
        const slipDateInput = document.getElementById('slip-date');
        const saveButton = document.getElementById('save-button');
        const savedSlipsList = document.getElementById('saved-slips-list');
        const notification = document.getElementById('notification');
        const refreshStatusesBtn = document.getElementById('refresh-statuses-btn');

        const today = new Date();
        slipDateInput.value = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

        let currentFile = null;
        let savedSlips = JSON.parse(localStorage.getItem('bankSlipsWithStatusV1')) || [];

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => fileUploadArea.addEventListener(eventName, preventDefaults, false));
        function preventDefaults(e) { e.preventDefault(); e.stopPropagation(); }
        ['dragenter', 'dragover'].forEach(eventName => fileUploadArea.addEventListener(eventName, () => fileUploadArea.classList.add('highlight'), false));
        ['dragleave', 'drop'].forEach(eventName => fileUploadArea.addEventListener(eventName, () => fileUploadArea.classList.remove('highlight'), false));
        fileUploadArea.addEventListener('drop', (e) => { if (e.dataTransfer.files.length > 0) handleFiles(e.dataTransfer.files[0]); }, false);
        fileUploadArea.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', function() { if (this.files.length > 0) handleFiles(this.files[0]); });
        function handleFiles(file) { currentFile = null; if (validateFile(file)) { currentFile = file; displayFile(file); } else { fileInput.value = ''; fileList.innerHTML = ''; } checkFormValidity(); }
        function validateFile(file) { const types = ['image/jpeg','image/png','application/pdf']; const maxSize = 5*1024*1024; if (!types.includes(file.type)) {showNotification('Invalid file type. Allowed: JPG, PNG, PDF. Detected: ' + (file.type || 'unknown'),'error'); return false;} if (file.size > maxSize) {showNotification('File is too large (Max 5MB).','error'); return false;} return true;}
        function displayFile(file) {fileList.innerHTML=''; const fi=document.createElement('div'); fi.className='file-item'; const fn=document.createElement('div'); fn.className='file-name'; fn.textContent=file.name; const rb=document.createElement('div'); rb.className='file-remove';rb.innerHTML='<i class="fas fa-times"></i>';rb.title='Remove';rb.onclick=(e)=>{e.stopPropagation();fileList.innerHTML='';currentFile=null;fileInput.value='';checkFormValidity();}; fi.appendChild(fn);fi.appendChild(rb);fileList.appendChild(fi);}

        function checkFormValidity() {
            if (!saveButton.classList.contains('btn--loading')) {
                saveButton.disabled = !(
                    usernameInput.value.trim() &&
                    slipDateInput.value &&
                    currentFile
                );
            }
        }
        [usernameInput, slipDateInput].forEach(i => {
            i.addEventListener('input', checkFormValidity);
            i.addEventListener('change', checkFormValidity);
        });

        saveButton.addEventListener('click', async function saveSlipToServer() {
            if (!currentFile || (saveButton.disabled && !saveButton.classList.contains('btn--loading'))) {
                 showNotification('Please ensure username, slip date are filled and a file is selected.', 'error'); return;
            }
            if (!validateFile(currentFile)) { // Re-validate before upload
                fileInput.value = ''; fileList.innerHTML = ''; currentFile = null; checkFormValidity();
                return;
            }
            saveButton.disabled = true;
            saveButton.classList.add('btn--loading');

            const formData = new FormData();
            formData.append('username', usernameInput.value.trim());
            formData.append('slipDate', slipDateInput.value);
            formData.append('slipFile', currentFile);

            try {
                const response = await fetch(window.location.pathname, { method: 'POST', body: formData });
                if (!response.ok) {
                    const errorText = await response.text();
                    let serverMessage = `Server error: ${response.status}`;
                    try {
                        const errorJson = JSON.parse(errorText);
                        if (errorJson && errorJson.message) {
                            serverMessage = errorJson.message;
                        } else {
                            serverMessage = errorText.substring(0,200);
                        }
                    } catch (e) { // Not a JSON error response
                        serverMessage = errorText.substring(0, 200);
                    }
                    throw new Error(serverMessage);
                }
                const result = await response.json().catch(e => {
                    throw new Error("Invalid JSON response from server during save.");
                });

                if (result.success) {
                    const slip = {
                        id: Date.now().toString(),
                        username: usernameInput.value.trim(),
                        date: slipDateInput.value,
                        originalFileName: result.originalFileName || currentFile.name,
                        serverFileName: result.serverFileName,
                        filePath: result.filePath, // This path comes from the server
                        status: result.initialStatus || 'Pending',
                        uploadDate: new Date().toISOString()
                    };
                    savedSlips.push(slip);
                    localStorage.setItem('bankSlipsWithStatusV1', JSON.stringify(savedSlips));

                    usernameInput.value = '';
                    const today = new Date();
                    slipDateInput.value = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
                    fileList.innerHTML = ''; currentFile = null; fileInput.value = '';

                    showNotification('Bank slip saved! Status: ' + slip.status, 'success');
                    renderSavedSlips();
                } else {
                    showNotification(result.message || 'Error saving slip. Please check details.', 'error');
                }
            } catch (error) {
                console.error('Upload/Save error:', error);
                showNotification(`Operation failed: ${error.message}. Check console/server logs.`, 'error');
            } finally {
                saveButton.classList.remove('btn--loading');
                checkFormValidity();
            }
        });

        let notificationTimeout;
        function showNotification(message, type) {
            clearTimeout(notificationTimeout);
            notification.textContent = message;
            notification.className = `notification ${type} visible`;
            notificationTimeout = setTimeout(() => { notification.classList.remove('visible'); }, 5000);
        }

        async function refreshSlipStatuses() {
            if (savedSlips.length === 0) { renderSavedSlips(); return; }
            refreshStatusesBtn.disabled = true;
            refreshStatusesBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';

            const pathsToQuery = savedSlips.map(slip => slip.filePath).filter(p => typeof p === 'string' && p.trim() !== '');
            if (pathsToQuery.length === 0) {
                 refreshStatusesBtn.disabled = false;
                 refreshStatusesBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
                 renderSavedSlips(); return;
            }

            try {
                const response = await fetch(`index.php?action=get_slip_statuses`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ filePaths: pathsToQuery })
                });

                if (!response.ok) {
                     const errorText = await response.text();
                     console.error("Server error raw response (refreshSlipStatuses):", errorText.substring(0, 500));
                     let serverMessage = `Server error fetching statuses: ${response.status}.`;
                     try {
                        const errorJson = JSON.parse(errorText);
                        if(errorJson && errorJson.error) serverMessage = errorJson.error;
                     } catch(e) { /* ignore if not json */ }
                     throw new Error(serverMessage);
                }

                const serverResponse = await response.json().catch(e => {
                     console.error("JSON parsing error on client (refreshSlipStatuses):", e);
                     throw new Error("Invalid JSON status response from server. Check console for details.");
                });

                if (serverResponse.error) {
                    throw new Error(`Server returned an error: ${serverResponse.error} ${serverResponse.details || ''}`);
                }

                const newStatuses = serverResponse;
                let updated = false;
                savedSlips.forEach(slip => {
                    if (slip.filePath && newStatuses[slip.filePath] && slip.status !== newStatuses[slip.filePath]) {
                        slip.status = newStatuses[slip.filePath];
                        updated = true;
                    } else if (slip.filePath && newStatuses[slip.filePath] === undefined && slip.status !== 'Not Found' && slip.status !== 'Error') {
                        // If a path was queried but server didn't return it, and it wasn't already an error/not found.
                        // This could mean the file was deleted on server, or other issue.
                        // Server should ideally return 'Not Found' or specific error for each queried path.
                        // For now, we can assume if it's missing, it's effectively not found by this refresh.
                        // slip.status = 'Not Found'; // Or 'Unknown'
                        // updated = true;
                        console.warn("Status for slip path '" + slip.filePath + "' not found in server response. Current status: " + slip.status);
                    }
                });

                if (updated) {
                    localStorage.setItem('bankSlipsWithStatusV1', JSON.stringify(savedSlips));
                    showNotification('Slip statuses updated from server.', 'success');
                } else {
                    showNotification('Statuses are up to date or no changes found.', 'success');
                }
            } catch (error) {
                console.error("Error refreshing statuses:", error);
                showNotification('Could not refresh statuses: ' + error.message, 'error');
            } finally {
                 refreshStatusesBtn.disabled = false;
                 refreshStatusesBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
                 renderSavedSlips();
            }
        }

        refreshStatusesBtn.addEventListener('click', refreshSlipStatuses);

        function renderSavedSlips() {
            savedSlipsList.innerHTML = '';
            if (savedSlips.length === 0) { savedSlipsList.innerHTML = '<p style="color: #A0A0A0; text-align:center; padding: 20px 0;">No saved bank slips found.</p>'; return; }
            const sortedSlips = [...savedSlips].sort((a, b) => new Date(b.uploadDate) - new Date(a.uploadDate));
            sortedSlips.forEach(slip => {
                const slipItem = document.createElement('div');
                const status = slip.status || 'Unknown';
                const statusSanitized = status.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
                const statusClass = 'status-' + statusSanitized;
                slipItem.className = `saved-slip-item ${statusClass}`;
                const slipInfo = document.createElement('div'); slipInfo.className = 'slip-info';
                const statusDisplay = `<span class="slip-status slip-status-${statusSanitized}">${htmlspecialchars(status)}</span>`;

                slipInfo.innerHTML = `<h3>File: ${htmlspecialchars(slip.originalFileName || 'N/A')} ${statusDisplay}</h3>
                                      <p><strong>Username:</strong> ${htmlspecialchars(slip.username || 'N/A')}</p>
                                      <p class="slip-date-info"><strong>Slip Date:</strong> ${formatDate(slip.date)} | <strong>Uploaded:</strong> ${formatDate(slip.uploadDate, true)}</p>`;

                const slipActions = document.createElement('div'); slipActions.className = 'slip-actions';
                const viewButton = document.createElement('button'); viewButton.innerHTML = '<i class="fas fa-eye"></i> View'; viewButton.title = 'View File';
                if (slip.filePath) { viewButton.addEventListener('click', () => window.open(slip.filePath, '_blank')); } else { viewButton.disabled = true; viewButton.style.opacity = "0.5"; viewButton.title = 'File path not available'; }
                const downloadButton = document.createElement('button'); downloadButton.innerHTML = '<i class="fas fa-download"></i> Download'; downloadButton.title = 'Download File';
                if (slip.filePath) { downloadButton.addEventListener('click', () => { const link = document.createElement('a'); link.href = slip.filePath; link.download = slip.originalFileName; document.body.appendChild(link); link.click(); document.body.removeChild(link); }); } else { downloadButton.disabled = true; downloadButton.style.opacity = "0.5"; downloadButton.title = 'File path not available';}
                const deleteButton = document.createElement('button'); deleteButton.innerHTML = '<i class="fas fa-trash-alt"></i> Delete (Local)'; deleteButton.title = 'Delete from this list';
                deleteButton.addEventListener('click', () => { if (confirm('Delete this slip from your local list? This will not delete it from the server.')){ savedSlips = savedSlips.filter(s => s.id !== slip.id); localStorage.setItem('bankSlipsWithStatusV1', JSON.stringify(savedSlips)); renderSavedSlips(); showNotification('Slip removed from local list.', 'success'); } });
                slipActions.appendChild(viewButton); slipActions.appendChild(downloadButton); slipActions.appendChild(deleteButton);
                slipItem.appendChild(slipInfo); slipItem.appendChild(slipActions);
                savedSlipsList.appendChild(slipItem);
            });
        }
        function formatDate(dateString, includeTime = false) { if (!dateString) return "N/A"; try { const date = new Date(dateString); if (isNaN(date.getTime())) return "Invalid Date"; const options = { year: 'numeric', month: 'short', day: 'numeric' }; if (includeTime) { options.hour = '2-digit'; options.minute = '2-digit'; } return date.toLocaleDateString(undefined, options); } catch (e) { return dateString; } }
        function htmlspecialchars(str) {
            if (typeof str !== 'string') return '';
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return str.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
        renderSavedSlips(); checkFormValidity(); refreshSlipStatuses();
    </script>
</body>
</html>
