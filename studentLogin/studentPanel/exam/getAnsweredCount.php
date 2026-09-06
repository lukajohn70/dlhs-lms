<?php
session_start();
require_once('../../../db_connection/dlhs_db_connection.php');

if (!isset($_POST['testId']) || !isset($_POST['studentId'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$testId = $_POST['testId'];
$studentId = $_POST['studentId'];

try {
    // Get the table names from tests table
    $query = "SELECT tableName, answersTable FROM tests WHERE testId = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("i", $testId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Test not found");
    }
    
    $row = $result->fetch_assoc();
    $questTableName = $row['tableName'];
    $answersTableName = $row['answersTable'];

    // Get total questions for this test
    $query = "SELECT COUNT(*) as total FROM `$questTableName` WHERE testId = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("i", $testId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $totalQuestions = $row['total'];

    // Get number of answered questions
    $query = "SELECT COUNT(*) as answered FROM `$answersTableName` 
              WHERE userLoginId = ? AND testId = ? AND selectedOption != '0'";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("ii", $studentId, $testId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $answeredCount = $row['answered'];

    echo json_encode([
        'success' => true,
        'answeredCount' => $answeredCount,
        'totalQuestions' => $totalQuestions
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 