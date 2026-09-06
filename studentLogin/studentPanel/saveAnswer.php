<?php
session_start();
if (!isset($_SESSION['studentLast_login'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit();
}

require_once('../../db_connection/dlhs_db_connection.php');
require_once('shuffle_options_helper.php');  // For reverse translation
require_once('answer_grading_helper.php');

$testId = $_SESSION['idOfTest'];
$studentId = $_SESSION['studentId'];

// Get POST data
$questionId = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
$answer = isset($_POST['answer']) ? $_POST['answer'] : '';

if (!$questionId || !$answer) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid question ID or answer']);
    exit();
}

// CRITICAL: Check if option randomization is enabled and reverse-translate if needed
$testQuery = "SELECT tableName, answersTable, randomizeOptions FROM tests WHERE testId = ?";
$testStmt = $connection->prepare($testQuery);
$testStmt->bind_param("i", $testId);
$testStmt->execute();
$testResult = $testStmt->get_result();

if ($testResult->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Test not found']);
    exit();
}

$testRow = $testResult->fetch_assoc();
$questionsTableName = $testRow['tableName'];
$answersTableName = $testRow['answersTable'];
$randomizeOptions = $testRow['randomizeOptions'] ?? 'No';

// If option randomization is enabled, reverse-translate the student's selection
$originalAnswer = $answer;
if ($randomizeOptions === 'Yes') {
    // Get the original question options
    $qQuery = "SELECT * FROM `$questionsTableName` WHERE questionId = ?";
    $qStmt = $connection->prepare($qQuery);
    $qStmt->bind_param("i", $questionId);
    $qStmt->execute();
    $qResult = $qStmt->get_result();
    
    if ($qResult->num_rows > 0) {
        $qRow = $qResult->fetch_assoc();
        $originalAnswer = reverseTranslateOption(
            $studentId,
            $questionId,
            $answer,  // What student selected (e.g., "C")
            $qRow['optionA'],
            $qRow['optionB'],
            $qRow['optionC'],
            $qRow['optionD'],
            $qRow['optionE']
        );
        // Now $originalAnswer contains the original letter (e.g., "B")
    }
}

// Use the original answer for saving (so grading works correctly)
$answer = $originalAnswer;

try {
    dlhsSaveAnswerWithMetadata(
        $connection,
        $answersTableName,
        $questionsTableName,
        (int) $studentId,
        (int) $testId,
        (int) $questionId,
        $answer
    );
    $success = true;
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Answer saved successfully' : 'Error saving answer'
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error saving answer: ' . $e->getMessage()
    ]);
} 
