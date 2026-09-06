<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";

// Check if user is logged in
if (!isset($_SESSION['staffLoggedIn']) || $_SESSION['staffLoggedIn'] !== "yes") {
    die("Unauthorized access");
}

$staffId = $_SESSION['staffId'];
$requestId = isset($_GET['requestId']) ? intval($_GET['requestId']) : 0;

if ($requestId <= 0) {
    die("Invalid Request ID");
}

// 1. Fetch request details to verify ownership
$reqQuery = "SELECT r.*, CONCAT(yg.yearGroupName, ' - ', c.className) AS className
             FROM file_requests r
             LEFT JOIN classes c ON r.classId = c.classId
             LEFT JOIN yeargroup yg ON c.classYearGroup = yg.yearGroupId
             WHERE r.requestId = ? AND r.teacherId = ? AND r.isActive = 1";

$stmt = $connection->prepare($reqQuery);
$stmt->bind_param("ii", $requestId, $staffId);
$stmt->execute();
$reqResult = $stmt->get_result();

if ($reqResult->num_rows === 0) {
    die("Request not found or unauthorized");
}

$requestDetails = $reqResult->fetch_assoc();
$stmt->close();

$requestTitle = preg_replace('/[^A-Za-z0-9_-]/', '_', $requestDetails['title']);

// 2. Fetch all submissions
$subQuery = "SELECT sub.*, s.surname, s.firstName 
             FROM file_request_submissions sub
             INNER JOIN studentlogin s ON sub.studentId = s.studentId
             WHERE sub.requestId = ?";

$stmt = $connection->prepare($subQuery);
$stmt->bind_param("i", $requestId);
$stmt->execute();
$subResult = $stmt->get_result();

if ($subResult->num_rows === 0) {
    die("No submissions found to download.");
}

// Create ZIP file
$zip = new ZipArchive();
$tempZipFile = tempnam(sys_get_temp_dir(), 'dlhs_zip');

if ($zip->open($tempZipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die("Failed to create temporary ZIP file");
}

$addedFilesCount = 0;
while ($row = $subResult->fetch_assoc()) {
    $filePath = $row['filePath'];
    
    // Check if file exists on disk
    if (file_exists($filePath)) {
        // Name format in zip: Surname_FirstName_OriginalName
        $studentName = preg_replace('/[^A-Za-z0-9_-]/', '_', $row['surname'] . '_' . $row['firstName']);
        $zipEntryName = $studentName . '_' . $row['originalName'];
        
        $zip->addFile($filePath, $zipEntryName);
        $addedFilesCount++;
    }
}

$zip->close();
$stmt->close();
$connection->close();

if ($addedFilesCount === 0) {
    unlink($tempZipFile);
    die("None of the submitted files exist on disk.");
}

// Stream ZIP file
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="Submissions_' . $requestTitle . '.zip"');
header('Content-Length: ' . filesize($tempZipFile));
header('Pragma: no-cache');
header('Expires: 0');

readfile($tempZipFile);
unlink($tempZipFile);
exit();
?>
