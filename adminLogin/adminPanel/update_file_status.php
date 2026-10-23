<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/file_assignment_helper.php";

dlhsEnsureAllFileManagementTablesExist($connection);

if (!isset($_SESSION['adminLoggedIn']) || $_SESSION['adminLoggedIn'] !== "yes") {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

$adminId = isset($_SESSION['adminId']) && is_numeric($_SESSION['adminId']) ? (int)$_SESSION['adminId'] : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fileId = isset($_POST['fileId']) ? (int)$_POST['fileId'] : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'reviewed';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

    if ($fileId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid file ID']);
        exit();
    }

    if (!in_array($status, ['pending', 'reviewed', 'acknowledged'])) {
        $status = 'reviewed';
    }

    $escapedNotes = mysqli_real_escape_string($connection, $notes);
    $escapedStatus = mysqli_real_escape_string($connection, $status);

    $query = "UPDATE file_uploads 
              SET adminStatus = '$escapedStatus', 
                  adminNotes = '$escapedNotes', 
                  adminReviewedAt = NOW(), 
                  adminReviewedBy = $adminId 
              WHERE fileId = $fileId";

    if ($connection->query($query)) {
        // Log review action
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = mysqli_real_escape_string($connection, $_SERVER['HTTP_USER_AGENT'] ?? '');
        $connection->query("INSERT INTO file_access_logs (fileId, userId, userType, action, ipAddress, userAgent) 
                            VALUES ($fileId, $adminId, 'admin', 'review_$status', '$ip', '$ua')");

        echo json_encode([
            'success' => true,
            'message' => 'Submission status updated successfully!',
            'status'  => $status,
            'notes'   => $notes
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $connection->error
        ]);
    }
    exit();
}
?>
