<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/file_assignment_helper.php";

dlhsEnsureFileStudentAssignmentsTable($connection);

// Check if user is logged in
if (!isset($_SESSION['staffLoggedIn']) || $_SESSION['staffLoggedIn'] !== "yes") {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

$staffId = $_SESSION['staffId'];

if (!isset($_POST['fileIds']) || !is_array($_POST['fileIds'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$fileIds = array_map('intval', $_POST['fileIds']);
$deleted = 0;
$errors = [];

foreach ($fileIds as $fileId) {
    // Get file details and verify ownership
    $query = "SELECT * FROM file_uploads WHERE fileId = $fileId AND uploadedBy = $staffId";
    $result = $connection->query($query);
    
    if ($result && $result->num_rows > 0) {
        $file = $result->fetch_assoc();
        
        // Delete physical file
        if (file_exists($file['filePath'])) {
            @unlink($file['filePath']);
        }
        
        // Delete from database
        $deleteQuery = "DELETE FROM file_uploads WHERE fileId = $fileId";
        if ($connection->query($deleteQuery)) {
            $deleted++;
            
            // Also delete related records
            $connection->query("DELETE FROM class_assignments WHERE fileId = $fileId");
            $connection->query("DELETE FROM file_student_assignments WHERE fileId = $fileId");
            $connection->query("DELETE FROM file_access_logs WHERE fileId = $fileId");
        } else {
            $errors[] = "Failed to delete file ID: $fileId";
        }
    } else {
        $errors[] = "File ID $fileId not found or not owned by you";
    }
}

echo json_encode([
    'success' => $deleted > 0,
    'deleted' => $deleted,
    'errors' => $errors,
    'message' => "$deleted file(s) deleted successfully"
]);
?>

