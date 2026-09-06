<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";

// Check if user is logged in
if (!isset($_SESSION['staffLoggedIn']) || $_SESSION['staffLoggedIn'] !== "yes") {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

$staffId = $_SESSION['staffId'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fileId = intval($_POST['fileId']);
    
    // Get file information
    $query = "SELECT * FROM file_uploads WHERE fileId = $fileId AND uploadedBy = $staffId";
    $result = $connection->query($query);
    
    if (!$result || $result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'File not found or not authorized']);
        exit();
    }
    
    $file = $result->fetch_assoc();
    
    // Check if file has submissions (if it's an assignment)
    if ($file['isAssignment']) {
        $submissionQuery = "SELECT COUNT(*) as count FROM assignment_submissions WHERE fileId = $fileId";
        $submissionResult = $connection->query($submissionQuery);
        if ($submissionResult && $submissionResult->num_rows > 0) {
            $submission = $submissionResult->fetch_assoc();
            if ($submission['count'] > 0) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete assignment with existing submissions']);
                exit();
            }
        }
    }
    
    // Delete file from disk
    if (file_exists($file['filePath'])) {
        unlink($file['filePath']);
    }
    
    // Delete from database
    $deleteQuery = "DELETE FROM file_uploads WHERE fileId = $fileId";
    
    if ($connection->query($deleteQuery)) {
        echo json_encode(['success' => true, 'message' => 'File deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $connection->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
