<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";

if (!isset($_SESSION['adminLoggedIn']) || $_SESSION['adminLoggedIn'] !== "yes") {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fileId = isset($_POST['fileId']) ? intval($_POST['fileId']) : 0;

    if ($fileId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid file ID']);
        exit();
    }

    $query = "SELECT * FROM file_uploads WHERE fileId = $fileId";
    $result = $connection->query($query);

    if (!$result || $result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'File not found']);
        exit();
    }

    $file = $result->fetch_assoc();

    // Delete physical file if it exists and is not a YouTube link
    if ($file['fileType'] !== 'youtube' && !empty($file['filePath'])) {
        $rootPath = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . ltrim(str_replace('../', '', $file['filePath']), '/\\');
        if (file_exists($rootPath) && is_file($rootPath)) {
            @unlink($rootPath);
        }
    }

    // Delete associated assignments and logs
    $connection->query("DELETE FROM file_staff_assignments WHERE fileId = $fileId");
    $connection->query("DELETE FROM file_student_assignments WHERE fileId = $fileId");
    $connection->query("DELETE FROM file_access_logs WHERE fileId = $fileId");

    // Delete from file_uploads
    if ($connection->query("DELETE FROM file_uploads WHERE fileId = $fileId")) {
        echo json_encode(['success' => true, 'message' => 'File deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $connection->error]);
    }
    exit();
}
?>
