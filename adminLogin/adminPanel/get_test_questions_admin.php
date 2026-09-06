<?php
session_start();
	error_reporting(0);
require_once 'userExpiredSession.php';

if (!isset($_SESSION['adminLoggedIn'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

include "../../db_connection/dlhs_db_connection.php";

$testId = isset($_POST['testId']) ? intval($_POST['testId']) : 0;

if ($testId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid test ID']);
    exit();
}

// Get test information
$testQuery = "SELECT * FROM tests WHERE testId = $testId";
$testResult = $connection->query($testQuery);

if (!$testResult || $testResult->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Test not found']);
    exit();
}

$test = $testResult->fetch_array(MYSQLI_NUM);
$questionsTableName = $test[12]; // Column 12 contains tableName
$duration = $test[4]; // Column 4 contains duration
$essayOption = $test[18]; // Column 18 contains essayOption (Yes/No)
$essayTime = $test[19]; // Column 19 contains essayTime

if (empty($questionsTableName)) {
    echo json_encode(['success' => false, 'message' => 'Questions table not specified']);
    exit();
}

// Check if questions table exists
$checkTable = $connection->query("SHOW TABLES LIKE '$questionsTableName'");
if (!$checkTable || $checkTable->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Questions table does not exist']);
    exit();
}

// Get all questions from the questions table
$questionsQuery = "SELECT * FROM `$questionsTableName` ORDER BY questionId";
$questionsResult = $connection->query($questionsQuery);

$questions = [];
if ($questionsResult && $questionsResult->num_rows > 0) {
    while ($row = $questionsResult->fetch_array(MYSQLI_NUM)) {
        /*
         * Typical question table structure (numeric indices):
         * 0 = questionId
         * 1 = question
         * 2 = optionA
         * 3 = optionB
         * 4 = optionC
         * 5 = optionD
         * 6 = (often optionE or empty)
         * 7 = correctOption
         * 8 = mark
         * 9 = solution (if exists)
         */
        
        $questions[] = [
            'questionId' => $row[0],
            'question' => htmlspecialchars_decode($row[1]),
            'optionA' => htmlspecialchars_decode($row[2]),
            'optionB' => htmlspecialchars_decode($row[3]),
            'optionC' => htmlspecialchars_decode($row[4]),
            'optionD' => htmlspecialchars_decode($row[5]),
            'correctOption' => $row[7],
            'mark' => $row[8],
            'solution' => isset($row[9]) ? htmlspecialchars_decode($row[9]) : ''
        ];
    }
}

// Get essay question if essay option is enabled
$essayQuestion = null;
if ($essayOption === 'Yes' || $essayOption === 'yes' || $essayOption == 1) {
    $essayQuery = "SELECT * FROM essay_questions WHERE testId = $testId";
    $essayResult = $connection->query($essayQuery);
    
    if ($essayResult && $essayResult->num_rows > 0) {
        $essayRow = $essayResult->fetch_array(MYSQLI_NUM);
        /*
         * essay_questions table structure:
         * 0 = id
         * 1 = testId
         * 2 = staffId
         * 3 = question
         */
        $essayQuestion = [
            'question' => htmlspecialchars_decode($essayRow[3]),
            'time' => $essayTime
        ];
    }
}

echo json_encode([
    'success' => true,
    'questions' => $questions,
    'essayQuestion' => $essayQuestion,
    'testInfo' => [
        'duration' => $duration,
        'totalQuestions' => count($questions),
        'hasEssay' => $essayOption === 'Yes' || $essayOption === 'yes' || $essayOption == 1,
        'essayTime' => $essayTime
    ]
]);
?>

