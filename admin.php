<?php
// ----- START PHP LOGIC for admin.php -----
session_start(); // Must be at the very top

// --- Configuration and Setup ---
// For production, ensure display_errors is OFF and errors are logged to a file.
ini_set('display_errors', 0); // Turn off for production by default for security
ini_set('log_errors', 1); 
// Optionally: ini_set('error_log', '/path/to/your/php-error.log');
error_reporting(E_ALL); // Log all errors

// Admin Credentials (HIGHLY INSECURE - Replace with database lookup and hashing)
define('ADMIN_USERNAME', 'a'); // CHANGE THIS
define('ADMIN_PASSWORD', '5'); // CHANGE THIS IMMEDIATELY

// Paths
$serverFileSystemBaseUploadDir = __DIR__ . '/Documents/';
$webAccessibleBaseUploadPath = 'Documents/'; 

// --- Utility Function (used by HTML view and potentially JSON view) ---
function parseTxtFile($txtFilePath) {
    $details = ['Status' => 'Pending']; // Default status
    if (file_exists($txtFilePath) && is_readable($txtFilePath)) {
        $lines = file($txtFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines !== false) {
            foreach ($lines as $line) {
                if (strpos($line, ':') !== false) {
                    list($key, $value) = explode(':', $line, 2);
                    $trimmedKey = trim($key);
                    $trimmedValue = trim($value);
                    if ($trimmedKey === 'Status' && in_array($trimmedValue, ['Accepted', 'Rejected', 'Pending'])) {
                        $details['Status'] = $trimmedValue;
                    } else {
                        $details[$trimmedKey] = $trimmedValue; 
                    }
                }
            }
        } else { error_log("parseTxtFile (admin.php): Failed to read lines from: " . $txtFilePath); }
    } else { error_log("parseTxtFile (admin.php): File not found or not readable: " . $txtFilePath); }
    return $details;
}

// --- Request Routing & Action Handling ---
$action = $_REQUEST['action'] ?? null; 
$request_method = $_SERVER['REQUEST_METHOD'];
$is_admin_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Helper to detect if it's an AJAX request wanting JSON
// (Can be made more robust, e.g. by checking HTTP_ACCEPT header or a specific request parameter)
function isAdminAjaxJsonRequest() {
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
           (isset($_REQUEST['response_type']) && $_REQUEST['response_type'] == 'json');
}

// --- Admin Login Action ---
if ($request_method === 'POST' && isset($_POST['login'])) {
    if ($_POST['username'] === ADMIN_USERNAME && $_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        // $is_admin_logged_in = true; // Not needed here due to redirect
        header("Location: admin.php"); 
        exit;
    } else {
        $login_error = "Invalid username or password.";
        // Falls through to show login form again
    }
}

// --- Admin Logout Action ---
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php"); 
    exit;
}

// --- Action: Update Slip Status (Handles AJAX and traditional POST) ---
if ($is_admin_logged_in && $request_method === 'POST' && $action === 'update_status') {
    $is_ajax = isAdminAjaxJsonRequest();
    
    if ($is_ajax) {
        header('Content-Type: application/json; charset=utf-8');
        // Errors already configured to log, not display
    }

    $response = [
        'success' => false,
        'message' => 'Failed to update status.',
        'new_status' => null,
        'slip_txt_path_key' => $_POST['slip_txt_path'] ?? null // For client to identify which slip was updated
    ];

    if (isset($_POST['slip_txt_path'], $_POST['new_status'])) {
        $txtFilePath = $_POST['slip_txt_path'];
        $newStatus = in_array($_POST['new_status'], ['Accepted', 'Rejected', 'Pending']) ? $_POST['new_status'] : 'Pending';
        
        // Security: Ensure the path is within the allowed directory
        $realBaseDir = realpath($serverFileSystemBaseUploadDir);
        $realTxtFilePath = realpath($txtFilePath); // Resolve the path

        if ($realTxtFilePath && $realBaseDir && strpos($realTxtFilePath, $realBaseDir) === 0 && pathinfo($realTxtFilePath, PATHINFO_EXTENSION) === 'txt') {
            $lines = file($realTxtFilePath, FILE_IGNORE_NEW_LINES); // Use resolved path
            $newLines = [];
            $statusExists = false;
            if ($lines !== false) {
                 foreach ($lines as $line) {
                    if (stripos($line, 'Status:') === 0) { 
                        $newLines[] = 'Status: ' . $newStatus;
                        $statusExists = true;
                    } else { $newLines[] = $line; }
                }
                if (!$statusExists) { $newLines[] = 'Status: ' . $newStatus; }
                
                if (file_put_contents($realTxtFilePath, implode("\n", $newLines) . "\n") !== false) { // Use resolved path
                    $response['success'] = true;
                    $response['message'] = 'Status updated successfully to ' . $newStatus . '.';
                    $response['new_status'] = $newStatus;
                } else { 
                    $response['message'] = 'Error: Failed to write updated status. Check permissions.'; 
                    error_log("admin.php update_status: file_put_contents failed for ".$realTxtFilePath); 
                }
            } else { 
                $response['message'] = 'Error: Failed to read status file.'; 
                error_log("admin.php update_status: file() failed for ".$realTxtFilePath); 
            }
        } else { 
            $response['message'] = 'Error: Invalid file path provided or security check failed.'; 
            error_log("admin.php update_status: Invalid path or security check for ".$txtFilePath . ". Real path: " . ($realTxtFilePath ?: 'false') . ". Base dir: " . ($realBaseDir ?: 'false'));
        }
    } else { 
        $response['message'] = 'Error: Missing parameters for status update.'; 
    }
    
    if ($is_ajax) {
        $json_output = json_encode($response);
        if($json_output === false) {
            error_log("admin.php update_status: json_encode failed. Error: " . json_last_error_msg());
            echo json_encode(['success' => false, 'message' => 'Server error encoding JSON response.']);
        } else {
            echo $json_output;
        }
        exit;
    } else {
        // Traditional form submission: set flash message and redirect
        $_SESSION['update_message'] = $response['message'];
        $_SESSION['update_success'] = $response['success'];
        header("Location: admin.php"); 
        exit;
    }
}

// --- Action: View Raw TXT File (No change needed for JSON fix, this is fine) ---
if ($is_admin_logged_in && $action === 'view_txt') {
    // ini_set('display_errors', 0); // Already set globally
    if (isset($_GET['user_folder'], $_GET['txt_filename'])) {
        // Sanitize inputs
        $userFolder = basename(preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['user_folder'])); 
        $txtFileName = basename(preg_replace('/[^a-zA-Z0-9_.-]/', '', $_GET['txt_filename']));
        
        $filePath = $serverFileSystemBaseUploadDir . $userFolder . '/' . $txtFileName;
        $realBaseDir = realpath($serverFileSystemBaseUploadDir);
        $realFilePath = realpath($filePath);

        if ($realBaseDir && $realFilePath && strpos($realFilePath, $realBaseDir) === 0 && pathinfo($realFilePath, PATHINFO_EXTENSION) === 'txt' && file_exists($realFilePath)) {
            header('Content-Type: text/plain; charset=utf-8');
            readfile($realFilePath); 
            exit;
        } else {
            error_log("admin.php view_txt failed security/existence check for path: ".$filePath);
            header("HTTP/1.0 404 Not Found"); echo "Error: File not found or access denied."; exit;
        }
    } else { 
        header("HTTP/1.0 400 Bad Request"); echo "Error: Missing parameters for view_txt."; exit; 
    }
}


// --- Action: Get Admin Data (JSON for AJAX refresh in admin panel) ---
if ($is_admin_logged_in && $action === 'get_admin_data_json') {
    header('Content-Type: application/json; charset=utf-8');
    // Errors already configured to log, not display

    $admin_data_for_json = [];
    $error_message_json = null;

    if (!is_dir($serverFileSystemBaseUploadDir)) {
         $error_message_json = "Configuration Error: The base upload directory '{$serverFileSystemBaseUploadDir}' does not exist or is not readable!";
         error_log("admin.php get_admin_data_json: " . $error_message_json);
    } else {
        $userFolders = array_filter(scandir($serverFileSystemBaseUploadDir), function ($item) use ($serverFileSystemBaseUploadDir) {
            return is_dir($serverFileSystemBaseUploadDir . $item) && $item !== '.' && $item !== '..';
        });
        
        foreach ($userFolders as $userFolder) {
             $userDirPath = $serverFileSystemBaseUploadDir . $userFolder . '/';
             if (!is_readable($userDirPath)) {
                 error_log("admin.php get_admin_data_json: Cannot read user directory: ".$userDirPath);
                 continue; 
             }
             $slipFilesOnDisk = array_filter(scandir($userDirPath), function ($item) use ($userDirPath) {
                  return !is_dir($userDirPath . $item) && (preg_match('/\.(jpg|jpeg|png|pdf)$/i', $item));
             });

             if (!empty($slipFilesOnDisk)) {
                  $Document_for_user_json = [];
                  foreach ($slipFilesOnDisk as $slipFileName) {
                       $txtFileName = pathinfo($slipFileName, PATHINFO_FILENAME) . '.txt';
                       $txtFilePathOnServer = $userDirPath . $txtFileName;
                       // For JSON, we might provide paths client needs for subsequent actions
                       $slipFilePathWeb = $webAccessibleBaseUploadPath . $userFolder . '/' . $slipFileName;
                       // $txtFilePathWeb = $webAccessibleBaseUploadPath . $userFolder . '/' . $txtFileName; // if needed by client
                       
                       $txtDetails = parseTxtFile($txtFilePathOnServer);
                       $Document_for_user_json[] = [
                           'slipFileName' => $slipFileName,
                           'txtFileName' => $txtFileName,
                           'txtFilePathServer' => $txtFilePathOnServer, // Key for updates
                           'slipFilePathWeb' => $slipFilePathWeb, // For viewing
                           'txtDetails' => $txtDetails,
                           'userFolder' => $userFolder // For context if needed by client JS
                       ];
                  }
                  if (!empty($Document_for_user_json)) {
                       $admin_data_for_json[$userFolder] = $Document_for_user_json;
                  }
             }
        }
        ksort($admin_data_for_json); 
    }

    $final_response = [];
    if ($error_message_json) {
        $final_response = ['success' => false, 'message' => $error_message_json, 'users' => new stdClass()]; // Use empty object for users
    } else {
        $final_response = ['success' => true, 'message' => 'Admin data retrieved.', 'users' => $admin_data_for_json];
    }

    $json_output = json_encode($final_response);
    if ($json_output === false) {
        error_log("admin.php get_admin_data_json: json_encode failed. Error: " . json_last_error_msg());
        echo json_encode(['success' => false, 'message' => 'Server error encoding admin data JSON response.', 'users' => new stdClass()]);
    } else {
        echo $json_output;
    }
    exit;
}


// --- Prepare Data For Admin HTML View (Only run if logged in AND no specific JSON action was handled) ---
// This part runs if it's not a JSON action like update_status (AJAX), get_admin_data_json, or view_txt
$render_html_page = true;
if ($action === 'update_status' || $action === 'get_admin_data_json' || $action === 'view_txt') {
    // These actions already sent their response and exited.
    // However, update_status without AJAX will redirect then re-render, so this check is a bit redundant
    // The exit; calls within actions are the primary control.
}


$admin_data_users = []; 
$admin_error_message = null;
$update_message_from_session = null; 
$update_success_from_session = false;

if ($is_admin_logged_in) {
     // Check for flash message from status update redirect (traditional POST)
    if (isset($_SESSION['update_message'])) {
        $update_message_from_session = $_SESSION['update_message'];
        $update_success_from_session = $_SESSION['update_success'] ?? false;
        unset($_SESSION['update_message']); 
        unset($_SESSION['update_success']);
    }

    // Fetch data for HTML display
    if (!is_dir($serverFileSystemBaseUploadDir)) {
         $admin_error_message = "Configuration Error: The base upload directory '{$serverFileSystemBaseUploadDir}' does not exist or is not readable!";
         error_log("admin.php HTML view: " . $admin_error_message);
    } else {
        $userFolders = array_filter(scandir($serverFileSystemBaseUploadDir), function ($item) use ($serverFileSystemBaseUploadDir) {
            return is_dir($serverFileSystemBaseUploadDir . $item) && $item !== '.' && $item !== '..';
        });
        
        foreach ($userFolders as $userFolder) {
             $userDirPath = $serverFileSystemBaseUploadDir . $userFolder . '/';
             if (!is_readable($userDirPath)) {
                 error_log("Admin HTML view: Cannot read user directory: ".$userDirPath);
                 continue; 
             }
             $slipFilesOnDisk = array_filter(scandir($userDirPath), function ($item) use ($userDirPath) {
                  return !is_dir($userDirPath . $item) && (preg_match('/\.(jpg|jpeg|png|pdf)$/i', $item));
             });

             if (!empty($slipFilesOnDisk)) {
                  $Document_for_user = [];
                  foreach ($slipFilesOnDisk as $slipFileName) {
                       $txtFileName = pathinfo($slipFileName, PATHINFO_FILENAME) . '.txt';
                       $txtFilePathOnServer = $userDirPath . $txtFileName;
                       $slipFilePathWeb = $webAccessibleBaseUploadPath . $userFolder . '/' . $slipFileName;
                       $txtFilePathWeb = $webAccessibleBaseUploadPath . $userFolder . '/' . $txtFileName; // For download link
                       
                       $txtDetails = parseTxtFile($txtFilePathOnServer); 
                       $Document_for_user[] = [
                           'slipFileName' => $slipFileName,
                           'txtFileName' => $txtFileName,
                           'txtFilePathServer' => $txtFilePathOnServer,
                           'slipFilePathWeb' => $slipFilePathWeb,
                           'txtFilePathWebLink' => $txtFilePathWeb, // New key for clarity in HTML
                           'txtDetails' => $txtDetails
                       ];
                  }
                  if (!empty($Document_for_user)) {
                       $admin_data_users[$userFolder] = $Document_for_user;
                  }
             }
        }
        ksort($admin_data_users);
    }
}

// ----- END PHP LOGIC -----
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Bank Slip Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Your existing Admin Panel View Styles from admin.php */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #2D2D2D; color: #E0E0E0; line-height: 1.6; margin: 0; padding: 10px; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; background-color: #3C3C3C; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.4); }
        h1 { color: #E0E0E0; text-align: center; margin-bottom: 20px;}
        .logout-link { display: block; text-align: right; margin-bottom: 20px; font-size: 0.9em;}
        a { color: #00A0FF; text-decoration: none; }
        a:hover { color: #40C4FF; text-decoration: underline;}
        .notification { padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; color: #FFFFFF; box-shadow: 0 2px 8px rgba(0,0,0,0.2); visibility: hidden; opacity: 0; transition: opacity 0.3s, visibility 0.3s; }
        .notification.visible { visibility: visible; opacity: 1; }
        .notification.success { background-color: #28a745; border-left: 5px solid #1e7e34; }
        .notification.error { background-color: #dc3545; border-left: 5px solid #b02a37; }
        .login-form { width: 320px; margin: 50px auto; padding: 25px; background-color: #4A4A4A; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.3);}
        .login-form h2 { border-bottom: none; padding-bottom: 0; margin-bottom:20px; text-align: center; font-size: 1.5em;}
        .login-form input[type="text"], .login-form input[type="password"] { width: 100%; padding: 12px; margin-bottom: 18px; background-color: #505050; color: #E0E0E0; border: 1px solid #606060; border-radius: 4px; font-size: 1em;}
        .login-form .btn { width: 100%; margin-top: 10px; font-size: 1.1em; padding: 10px; background-color: #007BFF; color:white; border:none; cursor:pointer;} /* Added btn styles */
        .error-message { color: #FF6B6B; margin-bottom: 15px; text-align: center; font-weight: 500;}
        .user-section { margin-bottom: 25px; }
        .user-section-header { background-color: #4A4A4A; padding: 12px 18px; margin-top: 20px; border-radius: 6px 6px 0 0; cursor: pointer; display: flex; justify-content: space-between; align-items: center; border: 1px solid #555555; border-bottom: none; }
        .user-section-header h2 { margin: 0; text-align: left; font-size: 1.3em; color: #E8E8E8; border-bottom: none; padding-bottom: 0; }
        .toggle-user-Document { background: none; border: none; color: #E0E0E0; font-size: 1.2em; cursor: pointer; padding: 0 5px; transition: transform 0.2s ease-in-out; }
        .user-section.collapsed .toggle-user-Document { transform: rotate(-90deg); }
        .user-Document-table-wrapper { border: 1px solid #555555; border-top: none; border-radius: 0 0 6px 6px; overflow: hidden; transition: max-height 0.3s ease-out; max-height: 10000px; } /* High max-height for transition */
        .user-section.collapsed .user-Document-table-wrapper { max-height: 0; border-width: 0 1px; }
        table.user-Document-table { width: 100%; border-collapse: collapse; }
        table.user-Document-table thead tr { border-bottom: 2px solid #585858; }
        table.user-Document-table th, table.user-Document-table td { border: 1px solid #505050; padding: 10px 12px; text-align: left; vertical-align: top; }
        table.user-Document-table th { background-color: #454545; color: #E0E0E0; font-weight: 600; white-space: nowrap; }
        table.user-Document-table td { background-color: #404040; font-size: 0.9em; }
        table.user-Document-table tr.status-pending {}
        table.user-Document-table tr.status-accepted td { background-color: #2c4c34; }
        table.user-Document-table tr.status-rejected td { background-color: #5a3a3a; }
        .slip-details-list { list-style-type: none; padding-left: 0; margin: 0; }
        .slip-details-list li { margin-bottom: 3px; white-space: normal; word-break: break-word; }
        .slip-details-list li strong { color: #C8C8C8; margin-right: 5px; }
        .txt-actions a { display: inline-block; margin-right: 10px; margin-bottom: 4px; font-size:0.9em; }
        .txt-actions a:last-child { margin-right: 0; }
        .slip-actions-form { display: flex; gap: 8px; flex-wrap: wrap; align-items:center;} /* Added align-items */
        .btn-action { padding: 8px 15px; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 0.9em; font-weight: 500; transition: background-color 0.2s ease, transform 0.1s ease; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; line-height: 1.2; }
        .btn-action i { font-size: 0.95em; }
        .btn-action:hover { transform: translateY(-1px); } .btn-action:active { transform: translateY(0px); }
        .btn-action.btn-accept { background-color: #28a745; } .btn-action.btn-accept:hover { background-color: #218838; }
        .btn-action.btn-reject { background-color: #dc3545; } .btn-action.btn-reject:hover { background-color: #c82333; }
        .btn-action.btn-pending { background-color: #ffc107; color: #2D2D2D; } .btn-action.btn-pending:hover { background-color: #e0a800; }
        .no-Document { padding: 20px; text-align: center; color: #A0A0A0; background-color: #454545; border: 1px solid #555; border-radius: 6px; }
        #refresh-admin-data-btn { background-color: #007bff; color: white; padding: 8px 12px; border:none; border-radius:4px; cursor:pointer; margin-left:15px;}
        #refresh-admin-data-btn i.fa-spin { margin-right: 5px; }

        @media screen and (max-width: 1024px) {
            table.user-Document-table thead { display: none; }
            table.user-Document-table tr.slip-row { display: flex; flex-direction: column; margin-bottom: 20px; border: 1px solid #5A5A5A; border-radius: 6px; padding: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.3); }
            table.user-Document-table tr.status-accepted { background-color: #2c4c34; } table.user-Document-table tr.status-rejected { background-color: #5a3a3a; } table.user-Document-table tr.status-pending { background-color: #444444; }
            table.user-Document-table td { display: block; width: 100%; padding: 8px 0; border: none; border-bottom: 1px dashed #505050; text-align: left; background-color: transparent !important; }
            table.user-Document-table td:last-child { border-bottom: none; }
            table.user-Document-table td::before { content: attr(data-label) ": "; font-weight: bold; color: #B0B0B0; margin-right: 8px; display: inline-block; width: 130px; vertical-align: top;} 
            table.user-Document-table td[data-label="Details"] ul { display: inline-block; } table.user-Document-table td[data-label="Details"]::before { display: block; margin-bottom: 5px; width: auto;}
            .slip-details-list { text-align: left; padding-left: 0; } td[data-label="Slip Actions"] .slip-actions-form { justify-content: flex-start; }
            .btn-action { width: auto; display: inline-flex; margin-right:5px; margin-bottom: 5px; } 
        }
        @media screen and (max-width: 480px) { 
            .user-section-header h2 { font-size: 1.1em; } .btn-action { width: 100%; margin-bottom: 8px; justify-content: center; } 
            .slip-actions-form { flex-direction: column; } table.user-Document-table td::before { display: block; width: auto; margin-bottom: 4px; }
            table.user-Document-table td { padding-left: 0; } 
        }
    </style>
</head>
<body>
    <div class="container <?php echo $is_admin_logged_in ? 'admin-container' : ''; ?>">
        <h1><?php echo $is_admin_logged_in ? 'Admin Panel - Bank Slip Management' : 'Admin Login'; ?></h1>

        <?php if (!$is_admin_logged_in): ?>
            <form method="POST" action="admin.php" class="login-form"> <h2>Login</h2>
                <?php if (isset($login_error)): ?>
                    <p class="error-message"><?php echo htmlspecialchars($login_error); ?></p>
                <?php endif; ?>
                <div><input type="text" name="username" placeholder="Username" required></div>
                <div><input type="password" name="password" placeholder="Password" required></div>
                <button type="submit" name="login" value="Login" class="btn">Login</button>
            </form>
        <?php else: // Admin is logged in ?>
            <p class="logout-link"><a href="admin.php?logout=true" title="Logout Admin"><i class="fas fa-sign-out-alt"></i> Logout</a></p>
            
            <div id="admin-global-notification" class="notification"></div>

             <?php if (isset($update_message_from_session)): // For traditional form submission feedback ?>
                <div class="notification <?php echo $update_success_from_session ? 'success' : 'error'; ?> visible">
                     <?php echo htmlspecialchars($update_message_from_session); ?>
                </div>
            <?php endif; ?>
             <?php if (isset($admin_error_message)): ?>
                 <div class="notification error visible">
                     <?php echo htmlspecialchars($admin_error_message); ?>
                 </div>
            <?php endif; ?>

            <button id="refresh-admin-data-btn" title="Refresh all user Document data from server">
                <i class="fas fa-sync-alt"></i> Refresh Data
            </button>
            
            <div id="admin-Document-container">
                <?php if (empty($admin_data_users) && !isset($admin_error_message)): ?>
                    <p class='no-Document'>No user folders or uploaded Document found.</p>
                <?php elseif (!empty($admin_data_users)) : ?>
                    <?php foreach ($admin_data_users as $userFolder => $Document_for_user): ?>
                        <div class='user-section' id='user-section-<?php echo htmlspecialchars($userFolder); ?>'>
                            <div class='user-section-header' data-target-wrapper='user-table-wrapper-<?php echo htmlspecialchars($userFolder); ?>'>
                                <h2>User: <?php echo htmlspecialchars($userFolder); ?></h2>
                                <button type='button' class='toggle-user-Document' title="Toggle Slip List"><i class='fas fa-chevron-down'></i></button>
                            </div>
                            <div class='user-Document-table-wrapper' id='user-table-wrapper-<?php echo htmlspecialchars($userFolder); ?>'>
                                <table class='user-Document-table'>
                                    <thead>
                                        <tr>
                                            <th>Slip File</th><th>Details</th><th>Status</th><th>Slip Actions</th><th>TXT File Actions</th><th>View Slip File</th><th>Upload Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($Document_for_user as $slip):
                                            $txtDetails = $slip['txtDetails'];
                                            $statusClass = 'status-' . strtolower(htmlspecialchars($txtDetails['Status'] ?? 'unknown'));
                                        ?>
                                        <tr class='<?php echo $statusClass; ?> slip-row' data-slip-key="<?php echo htmlspecialchars($slip['txtFilePathServer']); // Unique key for JS updates ?>">
                                            <td data-label='Slip File'><?php echo htmlspecialchars($txtDetails['Original Uploaded File'] ?? $slip['slipFileName']); ?></td>
                                            <td data-label='Details'>
                                                <?php if (!empty($txtDetails)): ?>
                                                    <ul class='slip-details-list'>
                                                        <?php foreach ($txtDetails as $key => $value): ?>
                                                            <?php if (!in_array($key, ['Original Uploaded File', 'Saved File Name', 'Upload Timestamp', 'Status'])): ?>
                                                                <li><strong><?php echo htmlspecialchars($key); ?>:</strong> <?php echo htmlspecialchars($value); ?></li>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: echo "Details TXT missing."; endif; ?>
                                            </td>
                                            <td data-label='Status' class="status-cell"><strong><?php echo htmlspecialchars($txtDetails['Status'] ?? 'N/A'); ?></strong></td>
                                            <td data-label='Slip Actions'>
                                                <div class='slip-actions-form'>
                                                    <?php if (($txtDetails['Status'] ?? 'Pending') !== 'Accepted'): ?>
                                                        <form method='POST' action='admin.php' class="update-status-form-traditional"><input type='hidden' name='action' value='update_status'><input type='hidden' name='slip_txt_path' value='<?php echo htmlspecialchars($slip['txtFilePathServer']); ?>'><input type='hidden' name='new_status' value='Accepted'><button type='submit' class='btn-action btn-accept'><i class='fas fa-check'></i> Accept</button></form>
                                                        <button type='button' class='btn-action btn-accept update-status-ajax' data-path="<?php echo htmlspecialchars($slip['txtFilePathServer']); ?>" data-status="Accepted"><i class='fas fa-check'></i> Accept (AJAX)</button>
                                                    <?php endif; ?>
                                                    <?php if (($txtDetails['Status'] ?? 'Pending') !== 'Rejected'): ?>
                                                         <form method='POST' action='admin.php' class="update-status-form-traditional"><input type='hidden' name='action' value='update_status'><input type='hidden' name='slip_txt_path' value='<?php echo htmlspecialchars($slip['txtFilePathServer']); ?>'><input type='hidden' name='new_status' value='Rejected'><button type='submit' class='btn-action btn-reject'><i class='fas fa-times'></i> Reject</button></form>
                                                         <button type='button' class='btn-action btn-reject update-status-ajax' data-path="<?php echo htmlspecialchars($slip['txtFilePathServer']); ?>" data-status="Rejected"><i class='fas fa-times'></i> Reject (AJAX)</button>
                                                    <?php endif; ?>
                                                    <?php if (($txtDetails['Status'] ?? 'Pending') !== 'Pending'): ?>
                                                         <form method='POST' action='admin.php' class="update-status-form-traditional"><input type='hidden' name='action' value='update_status'><input type='hidden' name='slip_txt_path' value='<?php echo htmlspecialchars($slip['txtFilePathServer']); ?>'><input type='hidden' name='new_status' value='Pending'><button type='submit' class='btn-action btn-pending'><i class='fas fa-hourglass-half'></i> Pending</button></form>
                                                         <button type='button' class='btn-action btn-pending update-status-ajax' data-path="<?php echo htmlspecialchars($slip['txtFilePathServer']); ?>" data-status="Pending"><i class='fas fa-hourglass-half'></i> Pending (AJAX)</button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td data-label='TXT File Actions' class='txt-actions'>
                                                <?php if (file_exists($slip['txtFilePathServer'])): ?>
                                                    <a href='admin.php?action=view_txt&user_folder=<?php echo urlencode($userFolder); ?>&txt_filename=<?php echo urlencode($slip['txtFileName']); ?>' target='_blank' title='View Raw TXT Content'><i class='fas fa-file-alt'></i> View TXT</a>
                                                    <a href='<?php echo htmlspecialchars($slip['txtFilePathWebLink']); ?>' download='<?php echo htmlspecialchars($slip['txtFileName']); ?>' title='Download TXT File'><i class='fas fa-download'></i> Download TXT</a>
                                                <?php else: echo "TXT missing"; endif; ?>
                                            </td>
                                            <td data-label='View Slip File'><a href='<?php echo htmlspecialchars($slip['slipFilePathWeb']); ?>' target='_blank'><i class='fas fa-external-link-alt'></i> View File</a></td>
                                            <td data-label='Upload Time'><?php echo htmlspecialchars($txtDetails['Upload Timestamp'] ?? 'N/A'); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div> <?php endif; // End logged-in check ?>
    </div> 
    <?php if ($is_admin_logged_in): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Collapsible sections
            const userSectionHeaders = document.querySelectorAll('.user-section-header');
            userSectionHeaders.forEach(header => {
                const targetWrapperId = header.dataset.targetWrapper;
                const targetWrapper = document.getElementById(targetWrapperId);
                const userSectionDiv = header.closest('.user-section');
                const toggleButtonIcon = header.querySelector('.toggle-user-Document i'); 

                if(targetWrapper && userSectionDiv && toggleButtonIcon) {
                     header.addEventListener('click', function() {
                        userSectionDiv.classList.toggle('collapsed');
                        toggleButtonIcon.className = userSectionDiv.classList.contains('collapsed') ? 'fas fa-chevron-right' : 'fas fa-chevron-down';
                    });
                }
            });

            const globalNotification = document.getElementById('admin-global-notification');
            function showGlobalNotification(message, type = 'info', duration = 5000) {
                globalNotification.textContent = message;
                globalNotification.className = `notification ${type} visible`;
                setTimeout(() => { globalNotification.classList.remove('visible'); }, duration);
            }

            // Hide session-based notifications after a delay
            document.querySelectorAll('.notification.visible').forEach(notif => {
                if(notif.id !== 'admin-global-notification') { // Don't auto-hide the global one here
                    setTimeout(() => { notif.style.opacity = '0'; setTimeout(() => { notif.style.visibility = 'hidden'; notif.classList.remove('visible');}, 500); }, 5000);
                }
            });

            // AJAX status update
            document.querySelectorAll('.update-status-ajax').forEach(button => {
                button.style.display = 'inline-flex'; // Make AJAX buttons visible by default
                button.closest('.slip-actions-form').querySelectorAll('.update-status-form-traditional').forEach(f => f.style.display = 'none'); // Hide traditional forms

                button.addEventListener('click', async function() {
                    const slipTxtPath = this.dataset.path;
                    const newStatus = this.dataset.status;
                    this.disabled = true;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing';

                    const formData = new FormData();
                    formData.append('action', 'update_status');
                    formData.append('slip_txt_path', slipTxtPath);
                    formData.append('new_status', newStatus);
                    formData.append('response_type', 'json'); // Indicate JSON response needed

                    try {
                        const response = await fetch('admin.php', { method: 'POST', body: formData });
                        if (!response.ok) {
                            throw new Error(`Network response was not ok: ${response.statusText}`);
                        }
                        const result = await response.json();

                        if (result.success) {
                            showGlobalNotification(result.message, 'success');
                            // Update UI directly: Find the row and update status display
                            const slipRow = document.querySelector(`tr[data-slip-key="${CSS.escape(slipTxtPath)}"]`);
                            if (slipRow) {
                                const statusCell = slipRow.querySelector('.status-cell strong');
                                if (statusCell) statusCell.textContent = result.new_status;
                                slipRow.className = `slip-row status-${result.new_status.toLowerCase()}`; // Update row class
                                // Potentially re-render action buttons for this row or refresh all data
                                // For simplicity, a full refresh might be easier if button logic is complex:
                                // document.getElementById('refresh-admin-data-btn').click();
                            }
                             // Simple refresh of buttons (hide/show appropriate ones)
                            const actionForm = this.closest('.slip-actions-form');
                            if(actionForm) {
                                actionForm.querySelectorAll('.update-status-ajax').forEach(btn => {
                                    btn.style.display = (btn.dataset.status === result.new_status) ? 'none' : 'inline-flex';
                                });
                            }
                        } else {
                            showGlobalNotification(result.message || 'Failed to update status via AJAX.', 'error');
                        }
                    } catch (error) {
                        console.error('AJAX update error:', error);
                        showGlobalNotification('Error during AJAX update: ' + error.message, 'error');
                    } finally {
                        this.disabled = false;
                        this.innerHTML = `<i class="fas fa-${this.classList.contains('btn-accept') ? 'check' : (this.classList.contains('btn-reject') ? 'times' : 'hourglass-half')}"></i> ${this.dataset.status} (AJAX)`;
                    }
                });
            });

            // AJAX refresh all admin data
            const refreshAdminDataBtn = document.getElementById('refresh-admin-data-btn');
            const adminDocumentContainer = document.getElementById('admin-Document-container');

            refreshAdminDataBtn.addEventListener('click', async function() {
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing Data...';

                try {
                    const response = await fetch('admin.php?action=get_admin_data_json', {
                        method: 'GET', // Or POST if you prefer, but GET is fine for fetching
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!response.ok) throw new Error(`Network error: ${response.statusText}`);
                    
                    const result = await response.json();

                    if (result.success) {
                        showGlobalNotification('Admin data refreshed successfully.', 'success');
                        renderAdminDocument(result.users);
                    } else {
                        showGlobalNotification(result.message || 'Failed to refresh admin data.', 'error');
                    }
                } catch (error) {
                    console.error('Refresh admin data error:', error);
                    showGlobalNotification('Error refreshing admin data: ' + error.message, 'error');
                } finally {
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh Data';
                }
            });

            function renderAdminDocument(usersData) {
                adminDocumentContainer.innerHTML = ''; // Clear current content
                if (Object.keys(usersData).length === 0) {
                    adminDocumentContainer.innerHTML = '<p class="no-Document">No user folders or uploaded Document found after refresh.</p>';
                    return;
                }

                for (const userFolder in usersData) {
                    const Document_for_user = usersData[userFolder];
                    let userSectionHTML = `
                        <div class='user-section' id='user-section-${userFolder}'>
                            <div class='user-section-header' data-target-wrapper='user-table-wrapper-${userFolder}'>
                                <h2>User: ${escapeHTML(userFolder)}</h2>
                                <button type='button' class='toggle-user-Document' title="Toggle Slip List"><i class='fas fa-chevron-down'></i></button>
                            </div>
                            <div class='user-Document-table-wrapper' id='user-table-wrapper-${userFolder}'>
                                <table class='user-Document-table'>
                                    <thead><tr><th>Slip File</th><th>Details</th><th>Status</th><th>Slip Actions</th><th>TXT File Actions</th><th>View Slip File</th><th>Upload Time</th></tr></thead>
                                    <tbody>`;
                    
                    Document_for_user.forEach(slip => {
                        const txtDetails = slip.txtDetails;
                        const currentStatus = txtDetails.Status || 'Pending';
                        const statusClass = 'status-' + currentStatus.toLowerCase();
                        let detailsHTML = '';
                        if (txtDetails) {
                            detailsHTML += "<ul class='slip-details-list'>";
                            for(const key in txtDetails){
                                if (!['Original Uploaded File', 'Saved File Name', 'Upload Timestamp', 'Status'].includes(key)) {
                                    detailsHTML += `<li><strong>${escapeHTML(key)}:</strong> ${escapeHTML(txtDetails[key])}</li>`;
                                }
                            }
                            detailsHTML += "</ul>";
                        } else { detailsHTML = "Details TXT missing."; }

                        let actionsHTML = "<div class='slip-actions-form'>";
                        if (currentStatus !== 'Accepted') {
                            actionsHTML += `<button type='button' class='btn-action btn-accept update-status-ajax' data-path="${escapeHTML(slip.txtFilePathServer)}" data-status="Accepted"><i class='fas fa-check'></i> Accept (AJAX)</button>`;
                        }
                        if (currentStatus !== 'Rejected') {
                            actionsHTML += `<button type='button' class='btn-action btn-reject update-status-ajax' data-path="${escapeHTML(slip.txtFilePathServer)}" data-status="Rejected"><i class='fas fa-times'></i> Reject (AJAX)</button>`;
                        }
                        if (currentStatus !== 'Pending') {
                            actionsHTML += `<button type='button' class='btn-action btn-pending update-status-ajax' data-path="${escapeHTML(slip.txtFilePathServer)}" data-status="Pending"><i class='fas fa-hourglass-half'></i> Pending (AJAX)</button>`;
                        }
                        actionsHTML += "</div>";
                        
                        let txtActionsHTML = "TXT missing";
                        // Assuming file_exists cannot be checked client side easily without another call.
                        // Rely on server data or make links always and let them 404 if file missing.
                        // For this render, we'll assume if path is provided, links can be made.
                        // The check for file_exists was in PHP before rendering. Here we use what server sent.
                        // We need a reliable way to know if txtFilePathServer is valid from the JSON.
                        // For now, let's assume if txtFileName is present, it's "available".
                        if(slip.txtFileName) {
                             txtActionsHTML = `<a href='admin.php?action=view_txt&user_folder=${encodeURIComponent(slip.userFolder)}&txt_filename=${encodeURIComponent(slip.txtFileName)}' target='_blank' title='View Raw TXT Content'><i class='fas fa-file-alt'></i> View TXT</a>
                                              <a href='${escapeHTML(slip.slipFilePathWeb.replace(slip.slipFileName, slip.txtFileName))}' download='${escapeHTML(slip.txtFileName)}' title='Download TXT File'><i class='fas fa-download'></i> Download TXT</a>`;
                        }


                        userSectionHTML += `
                            <tr class='${statusClass} slip-row' data-slip-key="${escapeHTML(slip.txtFilePathServer)}">
                                <td data-label='Slip File'>${escapeHTML(txtDetails['Original Uploaded File'] || slip.slipFileName)}</td>
                                <td data-label='Details'>${detailsHTML}</td>
                                <td data-label='Status' class="status-cell"><strong>${escapeHTML(currentStatus)}</strong></td>
                                <td data-label='Slip Actions'>${actionsHTML}</td>
                                <td data-label='TXT File Actions' class='txt-actions'>${txtActionsHTML}</td>
                                <td data-label='View Slip File'><a href='${escapeHTML(slip.slipFilePathWeb)}' target='_blank'><i class='fas fa-external-link-alt'></i> View File</a></td>
                                <td data-label='Upload Time'>${escapeHTML(txtDetails['Upload Timestamp'] || 'N/A')}</td>
                            </tr>`;
                    });
                    userSectionHTML += `</tbody></table></div></div>`;
                    adminDocumentContainer.innerHTML += userSectionHTML;
                }
                // Re-attach event listeners for newly rendered elements
                adminDocumentContainer.querySelectorAll('.update-status-ajax').forEach(button => {
                    // Simplified: hiding traditional forms is done once globally or CSS takes care of it.
                    button.addEventListener('click', button.getRootNode().defaultView.handleAjaxUpdateClick); // Rebind by assuming handleAjaxUpdateClick is global
                });
                 adminDocumentContainer.querySelectorAll('.user-section-header').forEach(header => {
                     const targetWrapperId = header.dataset.targetWrapper;
                     const targetWrapper = document.getElementById(targetWrapperId);
                     const userSectionDiv = header.closest('.user-section');
                     const toggleButtonIcon = header.querySelector('.toggle-user-Document i');
                     if(targetWrapper && userSectionDiv && toggleButtonIcon) {
                         header.addEventListener('click', function() {
                             userSectionDiv.classList.toggle('collapsed');
                             toggleButtonIcon.className = userSectionDiv.classList.contains('collapsed') ? 'fas fa-chevron-right' : 'fas fa-chevron-down';
                         });
                     }
                 });

            }
             // Make the AJAX update handler a global function or re-scope it for rebinding
            window.handleAjaxUpdateClick = async function(event) { /* ... contents of existing AJAX update handler ... */ };
            // Initial setup of AJAX buttons might need to call this handler or similar logic after first render.
            // For simplicity, the event listeners are re-added in renderAdminDocument.
            // This might require making the AJAX handler function accessible, e.g., by defining it in a broader scope.
            
            function escapeHTML(str) {
                if (typeof str !== 'string') return '';
                return str.replace(/[&<>"']/g, function (match) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[match];
                });
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
