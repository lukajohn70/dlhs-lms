<?php
session_start();
if (!isset($_SESSION['studentLast_login'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit();
}

$testId = isset($_SESSION['idOfTest']) ? (int) $_SESSION['idOfTest'] : 0;
$studentId = isset($_SESSION['studentId']) ? (int) $_SESSION['studentId'] : 0;
$questionsIdsArray = $_SESSION['questionsIdsArray'] ?? [];

// Release session lock early to prevent blocking concurrent requests (timer, tracking, status)
session_write_close();

require_once('../../db_connection/dlhs_db_connection.php');
require_once('shuffle_options_helper.php');
require_once('../../scripts/question_authoring_helper.php');

try {
    if (empty($questionsIdsArray) || $testId <= 0 || $studentId <= 0) {
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
    $tableStmt->close();

    $questionsTable = $tableRow['tableName'];
    $answersTable = $tableRow['answersTable'];
    $randomizeOptions = $tableRow['randomizeOptions'] ?? 'No';

    // Validate table names to prevent SQL injection
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $questionsTable) || !preg_match('/^[a-zA-Z0-9_]+$/', $answersTable)) {
        throw new Exception("Invalid question or answer table configuration");
    }

    $cleanQuestionIds = array_values(array_filter(array_map('intval', $questionsIdsArray)));
    if (empty($cleanQuestionIds)) {
        throw new Exception("No valid questions found for this test.");
    }

    $idList = implode(',', $cleanQuestionIds);
    $stimulusMap = dlhsFetchSharedStimulusMapForQuestions($connection, $testId, $cleanQuestionIds);

    // Batch-fetch all questions and existing student answers in a single query
    $batchQuery = "
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
            q.questionId IN ($idList)
    ";

    $batchStmt = $connection->prepare($batchQuery);
    $batchStmt->bind_param("ii", $studentId, $testId);
    $batchStmt->execute();
    $batchResult = $batchStmt->get_result();

    $rawQuestions = [];
    while ($r = $batchResult->fetch_assoc()) {
        $rawQuestions[(int) $r['questionId']] = $r;
    }
    $batchStmt->close();

    // Reconstruct questions in the exact order specified by $cleanQuestionIds (respects randomization)
    $questions = [];
    foreach ($cleanQuestionIds as $questionId) {
        if (!isset($rawQuestions[$questionId])) {
            continue;
        }

        $row = $rawQuestions[$questionId];
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
?>
