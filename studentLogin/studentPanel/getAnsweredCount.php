<?php
session_start();
require_once('../../db_connection/dlhs_db_connection.php');

if (!isset($_POST['testId']) || !isset($_POST['studentId'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$testId = $_POST['testId'];
$studentId = $_POST['studentId'];

try {
    // Get total questions for this test
    $query = "SELECT COUNT(*) as total FROM questions WHERE testId = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("i", $testId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $totalQuestions = $row['total'];

    // Get answered questions count
    $query = "SELECT COUNT(*) as answered FROM answers WHERE testId = ? AND studentId = ? AND answer IS NOT NULL";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("ii", $testId, $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $answeredCount = $row['answered'];

    echo json_encode([
        'success' => true,
        'totalQuestions' => $totalQuestions,
        'answeredCount' => $answeredCount
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 