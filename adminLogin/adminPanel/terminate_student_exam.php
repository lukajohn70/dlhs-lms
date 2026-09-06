<?php
session_start();
	error_reporting(0);
require_once '../../db_connection/dlhs_db_connection.php';
require_once '../../track_test_activity.php';
require_once '../../studentLogin/studentPanel/answer_grading_helper.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['adminId'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$studentId = isset($_POST['studentId']) ? intval($_POST['studentId']) : 0;
$testId = isset($_POST['testId']) ? intval($_POST['testId']) : 0;
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

if (!$studentId || !$testId || empty($reason)) {
    die(json_encode(['success' => false, 'message' => 'Invalid data provided']));
}

try {
    // Get test details
    $testQuery = "SELECT * FROM tests WHERE testId = ?";
    $stmt = $connection->prepare($testQuery);
    $stmt->bind_param("i", $testId);
    $stmt->execute();
    $testResult = $stmt->get_result();
    
    if ($testResult->num_rows === 0) {
        die(json_encode(['success' => false, 'message' => 'Test not found']));
    }
    
    $test = $testResult->fetch_assoc();
    $testTestedTable = $test['examineesTableName'];
    $questionsTableName = $test['tableName'];
    $answersTableName = $test['answersTable'];

    dlhsRepairAnswerMetadata($connection, $answersTableName, $questionsTableName, $studentId, $testId);
    
    // Set test status to completed (2) and record termination
    $timeSubmitted = date('h:i A');
    $updateQuery = "UPDATE `$testTestedTable` 
                    SET testStatus = 2, 
                        timeSubmittedTest = ? 
                    WHERE testId = ? AND examineeUserId = ?";
    $stmt = $connection->prepare($updateQuery);
    $stmt->bind_param("sii", $timeSubmitted, $testId, $studentId);
    $stmt->execute();
    
    // Calculate score
    // Get total marks
    $totalQuery = "SELECT SUM(markForQuestion) AS theSum FROM `$questionsTableName`";
    $result = $connection->query($totalQuery);
    $totalToBeEarned = $result->fetch_assoc()['theSum'] ?? 0;
    
    // Get earned marks
    $earnedQuery = "SELECT SUM(markForQuestion) AS totalEarned 
                    FROM `$answersTableName` 
                    WHERE TRIM(UPPER(selectedOption)) = TRIM(UPPER(correctOption)) 
                    AND userLoginId = ? AND testId = ?";
    $stmt = $connection->prepare($earnedQuery);
    $stmt->bind_param("ii", $studentId, $testId);
    $stmt->execute();
    $totalEarned = $stmt->get_result()->fetch_assoc()['totalEarned'] ?? 0;
    
    // Get total questions
    $totalQuestionsResult = $connection->query("SELECT COUNT(*) as count FROM `$questionsTableName`");
    $totalQuestions = $totalQuestionsResult->fetch_assoc()['count'];
    
    // Get number of correct answers
    $correctQuery = "SELECT COUNT(*) as count 
                     FROM `$answersTableName` 
                     WHERE TRIM(UPPER(selectedOption)) = TRIM(UPPER(correctOption)) 
                     AND userLoginId = ? AND testId = ?";
    $stmt = $connection->prepare($correctQuery);
    $stmt->bind_param("ii", $studentId, $testId);
    $stmt->execute();
    $numberCorrect = $stmt->get_result()->fetch_assoc()['count'];
    
    // Update scores
    $scoreQuery = "UPDATE `$testTestedTable` 
                   SET totalToBeEarned = ?, 
                       totalEarned = ?, 
                       noOfQuestions = ?, 
                       noCorrect = ? 
                   WHERE testId = ? AND examineeUserId = ?";
    $stmt = $connection->prepare($scoreQuery);
    $stmt->bind_param("ddiii", $totalToBeEarned, $totalEarned, $totalQuestions, $numberCorrect, $testId, $studentId);
    $stmt->execute();
    
    // Mark test as completed in active_test_takers
    markTestCompleted($testId, $studentId);
    
    // Log the termination as a security violation
    $adminEmail = $_SESSION['adminEmail'] ?? 'Administrator';
    $violationLog = "ADMIN TERMINATION: Exam forcefully terminated by admin ($adminEmail). Reason: $reason";
    
    $logQuery = "INSERT INTO security_violations 
                 (studentId, testId, violation, reason, timestamp, adminTerminated, terminatedBy) 
                 VALUES (?, ?, ?, ?, NOW(), 1, ?)";
    $stmt = $connection->prepare($logQuery);
    $stmt->bind_param("iisss", $studentId, $testId, $violationLog, $reason, $adminEmail);
    $stmt->execute();
    
    // Get student name for response
    $studentQuery = "SELECT CONCAT(firstName, ' ', surname) as fullName FROM studentlogin WHERE studentId = ?";
    $stmt = $connection->prepare($studentQuery);
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $studentName = $stmt->get_result()->fetch_assoc()['fullName'] ?? 'Student';
    
    echo json_encode([
        'success' => true,
        'message' => "Exam for $studentName has been terminated. Score: $totalEarned/$totalToBeEarned"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>



