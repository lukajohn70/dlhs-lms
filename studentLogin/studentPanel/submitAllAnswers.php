<?php
session_start();
require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../track_test_activity.php";
require_once "shuffle_options_helper.php";  // For reverse translation
require_once "answer_grading_helper.php";

header('Content-Type: application/json; charset=utf-8');

$testId = isset($_SESSION['idOfTest']) ? (int) $_SESSION['idOfTest'] : (isset($_SESSION['testId']) ? (int) $_SESSION['testId'] : 0);
$studentId = isset($_SESSION['studentId']) ? (int) $_SESSION['studentId'] : 0;

if ($testId <= 0 || $studentId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Your exam session has expired. Please reopen the test and try again.']);
    exit;
}

$answers = isset($_POST['answer']) && is_array($_POST['answer']) ? $_POST['answer'] : array();

try {
    $query = "SELECT * FROM tests WHERE testId='$testId'";
    $result = $connection->query($query);
    if (!$result || !$result->num_rows) {
        throw new RuntimeException('Test record not found.');
    }

    $row = $result->fetch_assoc();
    $answersTableName = $row['answersTable'];
    $questionsTableName = $row['tableName'];
    $testTestedTable = $row['examineesTableName'];
    $randomizeOptions = $row['randomizeOptions'] ?? 'No';

    if (trim((string) $answersTableName) === '' || trim((string) $questionsTableName) === '' || trim((string) $testTestedTable) === '') {
        throw new RuntimeException('Test configuration is incomplete.');
    }

    foreach ($answers as $questionId => $selectedOption) {
        $questionId = (int) $questionId;
        $selectedOption = dlhsNormalizeOptionValue($selectedOption);

        if ($questionId <= 0 || $selectedOption === '') {
            continue;
        }

        if ($randomizeOptions === 'Yes') {
            $qQuery = "SELECT * FROM `$questionsTableName` WHERE questionId = '$questionId'";
            $qResult = $connection->query($qQuery);
            if ($qResult && $qResult->num_rows > 0) {
                $qRow = $qResult->fetch_assoc();
                $selectedOption = reverseTranslateOption(
                    $studentId,
                    $questionId,
                    $selectedOption,
                    $qRow['optionA'],
                    $qRow['optionB'],
                    $qRow['optionC'],
                    $qRow['optionD'],
                    $qRow['optionE']
                );
            }
        }

        dlhsSaveAnswerWithMetadata(
            $connection,
            $answersTableName,
            $questionsTableName,
            (int) $studentId,
            (int) $testId,
            (int) $questionId,
            $selectedOption
        );
    }

    dlhsRepairAnswerMetadata($connection, $answersTableName, $questionsTableName, (int) $studentId, (int) $testId);

    $timeSubmittedTest = date('h:i A');
    $setStatusTo = 2;
    $query1 = "UPDATE $testTestedTable SET testStatus ='$setStatusTo', timeSubmittedTest='$timeSubmittedTest' WHERE testId ='$testId' AND examineeUserId ='$studentId'";
    if (!$connection->query($query1)) {
        throw new RuntimeException('Unable to mark the test as submitted.');
    }

    markTestCompleted($testId);

    $query2 = "SELECT SUM(markForQuestion) AS theSum FROM $questionsTableName";
    $result2 = $connection->query($query2);
    $row2 = $result2 ? $result2->fetch_assoc() : array();
    $totalToBeEarned = isset($row2['theSum']) ? (float) $row2['theSum'] : 0;

    $query3 = "SELECT SUM(markForQuestion) AS totalEarned FROM $answersTableName WHERE selectedOption=correctOption AND userLoginId='$studentId' AND testId='$testId'";
    $result3 = $connection->query($query3);
    $row3 = $result3 ? $result3->fetch_assoc() : array();
    $totalEarned = isset($row3['totalEarned']) ? (float) $row3['totalEarned'] : 0;

    $query4 = "SELECT COUNT(*) AS totalQuestions FROM $questionsTableName";
    $result4 = $connection->query($query4);
    $row4 = $result4 ? $result4->fetch_assoc() : array();
    $totalQuestions = isset($row4['totalQuestions']) ? (int) $row4['totalQuestions'] : 0;

    $query5 = "SELECT COUNT(*) AS totalCorrect FROM $answersTableName WHERE selectedOption=correctOption AND userLoginId='$studentId' AND testId='$testId'";
    $result5 = $connection->query($query5);
    $row5 = $result5 ? $result5->fetch_assoc() : array();
    $numberCorrect = isset($row5['totalCorrect']) ? (int) $row5['totalCorrect'] : 0;

    $query6 = "UPDATE $testTestedTable SET totalToBeEarned ='$totalToBeEarned', totalEarned='$totalEarned', noOfQuestions='$totalQuestions', noCorrect='$numberCorrect' WHERE testId ='$testId' AND examineeUserId ='$studentId'";
    if (!$connection->query($query6)) {
        throw new RuntimeException('Unable to update the test summary.');
    }

    echo json_encode(['success' => true, 'message' => 'Objective section submitted successfully.']);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Objective submission failed: ' . $exception->getMessage(),
    ]);
}
