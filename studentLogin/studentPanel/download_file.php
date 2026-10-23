<?php
session_start();
require_once "../../db_connection/dlhs_db_connection.php";

// Check if user is logged in (either staff or student)
$isAuthorized = false;
$userId = 0;
$userType = '';

if (isset($_SESSION['staffLoggedIn']) && $_SESSION['staffLoggedIn'] === "yes") {
    $isAuthorized = true;
    $userId = (int)$_SESSION['staffId'];
    $userType = 'staff';
} elseif (isset($_SESSION['studentLoggedIn']) && $_SESSION['studentLoggedIn'] === "yes") {
    $isAuthorized = true;
    $userId = (int)$_SESSION['studentId'];
    $userType = 'student';
}

if (!$isAuthorized) {
    header("HTTP/1.1 403 Forbidden");
    echo "<h1>403 Forbidden</h1><p>You are not authorized to download this file.</p>";
    exit();
}

$fileId = isset($_GET['fileId']) ? (int)$_GET['fileId'] : 0;
if ($fileId <= 0) {
    header("HTTP/1.1 400 Bad Request");
    echo "<h1>400 Bad Request</h1><p>Invalid file ID.</p>";
    exit();
}

// Fetch file from database
$stmt = $connection->prepare("SELECT * FROM file_uploads WHERE fileId = ? AND isActive = 1");
$stmt->bind_param("i", $fileId);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    header("HTTP/1.1 404 Not Found");
    echo "<h1>404 Not Found</h1><p>File not found in database.</p>";
    exit();
}

$file = $result->fetch_assoc();

// Check access permissions
$hasAccess = false;
$uploaderType = $file['uploaderType'] ?? 'staff';
$uploaderId = (int)$file['uploadedBy'];

if ($userType === 'student') {
    // If uploaded by a student, only the uploader student can access (STRICTLY NO STUDENT-TO-STUDENT SHARING)
    if ($uploaderType === 'student') {
        $hasAccess = ($uploaderId === (int)$userId);
    } elseif (in_array($file['uploadedFor'], ['all_teachers', 'subject_teachers', 'specific_teachers', 'admin', 'specific_staff']) || (isset($file['targetScope']) && in_array($file['targetScope'], ['teachers', 'admin', 'staff']))) {
        // Restricted to staff / admin
        $hasAccess = false;
    } elseif ($file['uploadedFor'] === 'all') {
        $hasAccess = ($file['isPublishedToStudents'] == 1);
    } elseif ($file['uploadedFor'] === 'specific_class') {
        $studentQuery = "SELECT classId FROM studentlogin WHERE studentId = $userId";
        $studentResult = $connection->query($studentQuery);
        if ($studentResult && $studentResult->num_rows > 0) {
            $student = $studentResult->fetch_assoc();
            if ($file['targetClassId'] == $student['classId'] && $file['isPublishedToStudents'] == 1) {
                $hasAccess = true;
            }
        }
    } elseif ($file['uploadedFor'] === 'specific_students') {
        $assignCheck = $connection->query("SELECT 1 FROM file_student_assignments WHERE fileId = $fileId AND studentId = $userId LIMIT 1");
        $hasAccess = ($assignCheck && $assignCheck->num_rows > 0 && $file['isPublishedToStudents'] == 1);
    }
} elseif ($userType === 'staff') {
    if ($uploaderType === 'student') {
        $hasAccess = (isset($file['targetStaffId']) && (int)$file['targetStaffId'] === (int)$userId);
    } else {
        $hasAccess = true;
    }
}

if (!$hasAccess) {
    header("HTTP/1.1 403 Forbidden");
    echo "<h1>403 Forbidden</h1><p>Access denied. You do not have permission to access this file.</p>";
    exit();
}
$filePathDb = $file['filePath'];

// Resolve the actual file path on disk
$fullPath = __DIR__ . '/' . $filePathDb;

// Defensive fallback: If not found, try stripping leading "../../" and using relative path from root
if (!file_exists($fullPath)) {
    $cleanedPath = ltrim(str_replace('../', '', $filePathDb), '/');
    $fullPath = __DIR__ . '/../../' . $cleanedPath;
}

if (!file_exists($fullPath) || is_dir($fullPath)) {
    header("HTTP/1.1 404 Not Found");
    echo "<h1>404 Not Found</h1><p>File does not exist on disk.</p>";
    exit();
}

// Increment download count
$updateStmt = $connection->prepare("UPDATE file_uploads SET downloadCount = downloadCount + 1 WHERE fileId = ?");
$updateStmt->bind_param("i", $fileId);
$updateStmt->execute();

// Log access
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$logStmt = $connection->prepare("INSERT INTO file_access_logs (fileId, userId, userType, action, ipAddress, userAgent) VALUES (?, ?, ?, 'download', ?, ?)");
$logStmt->bind_param("iisss", $fileId, $userId, $userType, $ipAddress, $userAgent);
$logStmt->execute();

// Send file headers and stream the file
$originalName = $file['originalName'];
$fileSize = filesize($fullPath);
$mimeType = $file['fileType'] ?: 'application/octet-stream';

// Clear all output buffers to prevent corruption
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . basename($originalName) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . $fileSize);

readfile($fullPath);
exit();
?>
