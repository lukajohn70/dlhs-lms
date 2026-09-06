<?php
session_start();

if (!isset($_SESSION['studentId']) || !isset($_SESSION['idOfTest'])) {
    die('Unauthorized');
}

require_once('../../db_connection/dlhs_db_connection.php');
require_once('../../track_test_activity.php');

$testId = $_SESSION['idOfTest'];
$studentId = $_SESSION['studentId'];
$testedTableName = $_SESSION['testedTableName'] ?? '';
$totalQuestions = $_SESSION['totalQuestions'] ?? 0;
$answersTableName = $_SESSION['answersTableName'] ?? '';

// Get remaining time from POST or session
$remainingTime = isset($_POST['remainingTime']) ? intval($_POST['remainingTime']) : null;

// Get test name from tests table
$testName = '';
$query = "SELECT testName FROM tests WHERE testId = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("i", $testId);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $testName = $row['testName'];
}
$stmt->close();

// Count answered questions
$answeredCount = 0;
if ($answersTableName) {
    $countQuery = "SELECT COUNT(*) as count FROM `$answersTableName` WHERE userLoginId = ? AND testId = ? AND selectedOption != 0";
    $countStmt = $connection->prepare($countQuery);
    $countStmt->bind_param("ii", $studentId, $testId);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    if ($countRow = $countResult->fetch_assoc()) {
        $answeredCount = $countRow['count'];
    }
    $countStmt->close();
}

// Track the activity
trackTestActivity($testId, $testName, $testedTableName, $remainingTime, $answeredCount, $totalQuestions);

echo json_encode(['success' => true]);
?>



