<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";

header('Content-Type: application/json');

if (!isset($_SESSION['staffLoggedIn']) || $_SESSION['staffLoggedIn'] !== "yes") {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

$staffId = intval($_SESSION['staffId']);
$fileId  = isset($_POST['fileId']) ? intval($_POST['fileId']) : 0;

if ($fileId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid file ID']);
    exit();
}

// Only the teacher who uploaded the file can toggle visibility
$check = $connection->query("SELECT fileId, isPublishedToStudents FROM file_uploads WHERE fileId = $fileId AND uploadedBy = $staffId");
if (!$check || $check->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'File not found or access denied']);
    exit();
}

$row       = $check->fetch_assoc();
$newStatus = ($row['isPublishedToStudents'] == 1) ? 0 : 1;

$update = $connection->query("UPDATE file_uploads SET isPublishedToStudents = $newStatus WHERE fileId = $fileId AND uploadedBy = $staffId");
if ($update) {
    echo json_encode([
        'success'   => true,
        'newStatus' => $newStatus,
        'message'   => $newStatus ? 'File is now visible to students' : 'File is now hidden from students'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $connection->error]);
}
?>
