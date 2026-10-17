<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/file_request_helper.php";

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['staffLoggedIn']) || $_SESSION['staffLoggedIn'] !== "yes") {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$staffId = $_SESSION['staffId'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = isset($_POST['requestId']) ? intval($_POST['requestId']) : 0;

    if ($requestId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
        exit();
    }

    // Verify ownership
    $verifyQuery = "SELECT requestId FROM file_requests WHERE requestId = ? AND teacherId = ?";
    $stmt = $connection->prepare($verifyQuery);
    $stmt->bind_param("ii", $requestId, $staffId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Request not found or unauthorized']);
        exit();
    }
    $stmt->close();

    // Soft delete request
    $deleteQuery = "UPDATE file_requests SET isActive = 0 WHERE requestId = ?";
    $stmt = $connection->prepare($deleteQuery);
    $stmt->bind_param("i", $requestId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Request deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete request: ' . $connection->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$connection->close();
?>
