<?php
session_start();
if (!isset($_SESSION['studentLast_login'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit();
}

require_once('../../../db_connection/dlhs_db_connection.php');
require_once('../answer_grading_helper.php');

if (!isset($_POST['questionId']) || !isset($_POST['answer'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit();
}

$testId = $_POST['testId'];
$studentId = $_POST['studentId'];
$questionId = intval($_POST['questionId']);
$answer = $_POST['answer'];

try {
    // Get test details and table names
    $stmt = $connection->prepare("SELECT tableName, answersTable FROM tests WHERE testId = ?");
    $stmt->bind_param("i", $testId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Test not found");
    }

    $row = $result->fetch_assoc();
    $questionsTable = $row['tableName'];
    $answersTable = $row['answersTable'];

    dlhsSaveAnswerWithMetadata(
        $connection,
        $answersTable,
        $questionsTable,
        (int) $studentId,
        (int) $testId,
        (int) $questionId,
        $answer
    );

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Answer saved successfully']);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Error saving answer: ' . $e->getMessage()
    ]);
} 
