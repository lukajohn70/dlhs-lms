<?php
session_start();
if (!isset($_SESSION['studentLast_login'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit();
}

require_once('../../db_connection/dlhs_db_connection.php');
require_once('shuffle_options_helper.php');
require_once('../../scripts/question_authoring_helper.php');

$testId = $_SESSION['idOfTest'];
$studentId = $_SESSION['studentId'];

try {
    // Get the randomized question order from session (set by exam.php)
    $questionsIdsArray = $_SESSION['questionsIdsArray'] ?? [];
    
    if (empty($questionsIdsArray)) {
        throw new Exception("Question order not found in session. Please refresh the exam page.");
    }
    
    // First get the table names and randomization setting from the tests table
    $tableQuery = "SELECT tableName, answersTable, randomizeOptions FROM tests WHERE testId = ?";
    $tableStmt = $connection->prepare($tableQuery);
    $tableStmt->bind_param("i", $testId);
    $tableStmt->execute();
    $tableResult = $tableStmt->get_result();
    
    if ($tableResult->num_rows === 0) {
        throw new Exception("Test not found");
    }
    
    $tableRow = $tableResult->fetch_assoc();
    $questionsTable = $tableRow['tableName'];
    $answersTable = $tableRow['answersTable'];
    $randomizeOptions = $tableRow['randomizeOptions'] ?? 'No';
    
    // Fetch questions in the order specified by questionsIdsArray (respects randomization)
    $questions = [];
    
    $stimulusMap = dlhsFetchSharedStimulusMapForQuestions($connection, $testId, $questionsIdsArray);

    foreach ($questionsIdsArray as $questionId) {
        // Get question data
        $stmt = $connection->prepare("
            SELECT 
                q.questionId,
                q.question,
                q.optionA,
                q.optionB,
                q.optionC,
                q.optionD,
                q.optionE,
                q.correctOption,
                a.selectedOption
            FROM 
                `$questionsTable` q
            LEFT JOIN 
                `$answersTable` a ON q.questionId = a.questionId 
                AND a.userLoginId = ? 
                AND a.testId = ?
            WHERE
                q.questionId = ?
        ");
        
        $stmt->bind_param("iii", $studentId, $testId, $questionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if (!$row) {
            continue; // Skip if question not found
        }
        $optionA = htmlspecialchars_decode($row['optionA']);
        $optionB = htmlspecialchars_decode($row['optionB']);
        $optionC = htmlspecialchars_decode($row['optionC']);
        $optionD = htmlspecialchars_decode($row['optionD']);
        $optionE = htmlspecialchars_decode($row['optionE']);
        $correctOption = $row['correctOption'];
        $selectedOption = $row['selectedOption'];

        if ($randomizeOptions === 'Yes' && !empty($selectedOption) && $selectedOption !== '0') {
            $selectedOption = translateOriginalOptionToDisplayed(
                $studentId,
                (int) $row['questionId'],
                $selectedOption,
                $optionA,
                $optionB,
                $optionC,
                $optionD,
                $optionE
            );
        }
        
        // Apply option randomization if enabled (SAFE: Uses student+question as seed)
        if ($randomizeOptions === 'Yes') {
            $shuffled = shuffleAnswerOptions(
                $studentId,
                $row['questionId'],
                $optionA,
                $optionB,
                $optionC,
                $optionD,
                $optionE,
                $correctOption
            );
            $optionA = $shuffled['optionA'];
            $optionB = $shuffled['optionB'];
            $optionC = $shuffled['optionC'];
            $optionD = $shuffled['optionD'];
            $optionE = $shuffled['optionE'];
            $correctOption = $shuffled['correctOption'];
        }
        
        $stimulusMeta = isset($stimulusMap[(int) $row['questionId']]) ? $stimulusMap[(int) $row['questionId']] : null;

        $questions[] = [
            'questionId' => $row['questionId'],
            'question' => htmlspecialchars_decode($row['question']),
            'optionA' => $optionA,
            'optionB' => $optionB,
            'optionC' => $optionC,
            'optionD' => $optionD,
            'optionE' => $optionE,
            'correctOption' => $correctOption,
            'selectedOption' => $selectedOption,
            'stimulusId' => $stimulusMeta ? $stimulusMeta['stimulusId'] : 0,
            'stimulusType' => $stimulusMeta ? $stimulusMeta['stimulusType'] : '',
            'stimulusTitle' => $stimulusMeta ? $stimulusMeta['stimulusTitle'] : '',
            'stimulusContent' => $stimulusMeta ? $stimulusMeta['stimulusContent'] : ''
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($questions);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching questions: ' . $e->getMessage()
    ]);
} 
