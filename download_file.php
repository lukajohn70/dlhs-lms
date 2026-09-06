<?php
session_start();
require_once "db_connection/dlhs_db_connection.php";

// Check if user is logged in (any type)
$isLoggedIn = false;
$userId = null;
$userType = null;

if (isset($_SESSION['studentLoggedIn']) && $_SESSION['studentLoggedIn'] === "yes") {
    $isLoggedIn = true;
    $userId = $_SESSION['studentId'];
    $userType = 'student';
} elseif (isset($_SESSION['staffLoggedIn']) && $_SESSION['staffLoggedIn'] === "yes") {
    $isLoggedIn = true;
    $userId = $_SESSION['staffId'];
    $userType = 'staff';
} elseif (isset($_SESSION['adminLoggedIn']) && $_SESSION['adminLoggedIn'] === "yes") {
    $isLoggedIn = true;
    $userId = $_SESSION['adminId'];
    $userType = 'admin';
}

if (!$isLoggedIn) {
    header("HTTP/1.0 403 Forbidden");
    echo "Access denied. Please log in.";
    exit();
}

$type = isset($_GET['type']) ? $_GET['type'] : 'default';

if ($type === 'file_request') {
    $submissionId = isset($_GET['submissionId']) ? intval($_GET['submissionId']) : 0;
    if ($submissionId <= 0) {
        header("HTTP/1.0 400 Bad Request");
        echo "Invalid submission ID";
        exit();
    }
    
    // Fetch submission
    $query = "SELECT sub.*, req.teacherId 
              FROM file_request_submissions sub
              INNER JOIN file_requests req ON sub.requestId = req.requestId
              WHERE sub.submissionId = $submissionId";
    $result = $connection->query($query);
    
    if (!$result || $result->num_rows === 0) {
        header("HTTP/1.0 404 Not Found");
        echo "Submission not found";
        exit();
    }
    
    $submission = $result->fetch_assoc();
    
    // Access check: Only the requesting teacher (staff) or the submitting student can download
    $hasAccess = false;
    if ($userType === 'staff' && $userId == $submission['teacherId']) {
        $hasAccess = true;
    } elseif ($userType === 'student' && $userId == $submission['studentId']) {
        $hasAccess = true;
    } elseif ($userType === 'admin') {
        $hasAccess = true;
    }
    
    if (!$hasAccess) {
        header("HTTP/1.0 403 Forbidden");
        echo "Access denied. You do not have permission to download this submission.";
        exit();
    }
    
    $filePath = $submission['filePath'];
    
    // Try to resolve path relative to root if it starts with '../../'
    if (!file_exists($filePath)) {
        if (strpos($filePath, '../../') === 0) {
            $filePath = substr($filePath, 6); // remove '../../'
        }
    }
    
    if (!file_exists($filePath)) {
        header("HTTP/1.0 404 Not Found");
        echo "File not found on server.";
        exit();
    }
    
    // Clear all output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set headers
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($submission['originalName']) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));
    
    readfile($filePath);
    exit();
}

$fileId = isset($_GET['fileId']) ? intval($_GET['fileId']) : 0;

// Get file information
$query = "SELECT * FROM file_uploads WHERE fileId = $fileId AND isActive = 1";
$result = $connection->query($query);

if (!$result || $result->num_rows === 0) {
    header("HTTP/1.0 404 Not Found");
    echo "File not found";
    exit();
}

$file = $result->fetch_assoc();

// Check access permissions
$hasAccess = false;

if ($userType === 'admin' || $userType === 'staff') {
    // Admins and staff can access all files
    $hasAccess = true;
} elseif ($userType === 'student') {
    // Students can access files targeted to them
    if ($file['uploadedFor'] === 'all') {
        $hasAccess = true;
    } elseif ($file['uploadedFor'] === 'specific_class') {
        // Check if student is in the target class
        $studentQuery = "SELECT classId FROM studentlogin WHERE studentId = $userId";
        $studentResult = $connection->query($studentQuery);
        if ($studentResult && $studentResult->num_rows > 0) {
            $student = $studentResult->fetch_assoc();
            if ($file['targetClassId'] == $student['classId']) {
                $hasAccess = true;
            }
        }
    } elseif ($file['uploadedFor'] === 'specific_student' && $file['targetStudentId'] == $userId) {
        $hasAccess = true;
    }
}

if (!$hasAccess) {
    header("HTTP/1.0 403 Forbidden");
    echo "Access denied. You don't have permission to download this file.";
    exit();
}

// Check if file exists on disk
if (!file_exists($file['filePath'])) {
    header("HTTP/1.0 404 Not Found");
    echo "File not found on server";
    exit();
}

// Log the download
$logQuery = "INSERT INTO file_access_logs (fileId, userId, userType, action, ipAddress, userAgent) 
            VALUES ($fileId, $userId, '$userType', 'download', '" . $_SERVER['REMOTE_ADDR'] . "', '" . 
            mysqli_real_escape_string($connection, $_SERVER['HTTP_USER_AGENT']) . "')";
$connection->query($logQuery);

// Update download count
$updateQuery = "UPDATE file_uploads SET downloadCount = downloadCount + 1 WHERE fileId = $fileId";
$connection->query($updateQuery);

// Set headers for file download
header('Content-Type: ' . $file['fileType']);
header('Content-Disposition: attachment; filename="' . $file['originalName'] . '"');
header('Content-Length: ' . filesize($file['filePath']));
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Output file
readfile($file['filePath']);
exit();
?>
