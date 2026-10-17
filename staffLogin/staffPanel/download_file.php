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
$stmt = $connection->prepare("SELECT * FROM file_uploads WHERE fileId = ?");
$stmt->bind_param("i", $fileId);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    header("HTTP/1.1 404 Not Found");
    echo "<h1>404 Not Found</h1><p>File not found in database.</p>";
    exit();
}

$file = $result->fetch_assoc();
$filePathDb = $file['filePath'];

// Resolve the actual file path on disk
// The path in DB starts with '../../uploads/'
// The download_file.php script is in 'staffLogin/staffPanel/'
// So __DIR__ . '/' . $filePathDb works perfectly!
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
