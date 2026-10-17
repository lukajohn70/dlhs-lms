<?php
ob_start();
ini_set('display_errors', '0');
session_start();
$testId = isset($_SESSION['idOfTest']) ? (int) $_SESSION['idOfTest'] : (isset($_SESSION['testId']) ? (int) $_SESSION['testId'] : 0);
$studentId = isset($_SESSION['studentId']) ? (int) $_SESSION['studentId'] : 0;

if ($testId <= 0 && isset($_POST['testId'])) {
    $testId = (int) $_POST['testId'];
    $_SESSION['idOfTest'] = $testId;
    $_SESSION['testId'] = $testId;
}
if ($studentId <= 0 && isset($_POST['studentId'])) {
    $studentId = (int) $_POST['studentId'];
    $_SESSION['studentId'] = $studentId;
}
session_write_close();

require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../track_test_activity.php";
require_once "shuffle_options_helper.php";  // For reverse translation
require_once "answer_grading_helper.php";

header('Content-Type: application/json; charset=utf-8');

if ($testId <= 0 || $studentId <= 0) {
    if (ob_get_length()) {
        ob_clean();
    }
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

    $validAnswers = [];
    foreach ($answers as $qId => $selOpt) {
        $qId = (int) $qId;
        $norm = dlhsNormalizeOptionValue($selOpt);
        if ($qId > 0 && $norm !== '') {
            $validAnswers[$qId] = $norm;
        }
    }

    if (!empty($validAnswers)) {
        $qIdsList = implode(',', array_keys($validAnswers));
        $qQuery = "SELECT questionId, optionA, optionB, optionC, optionD, optionE, correctOption, markForQuestion FROM `$questionsTableName` WHERE questionId IN ($qIdsList)";
        $qResult = $connection->query($qQuery);
        $questionsMap = [];
        if ($qResult) {
            while ($qRow = $qResult->fetch_assoc()) {
                $questionsMap[(int) $qRow['questionId']] = $qRow;
            }
        }

        $existingAnswersQuery = "SELECT questionId, selectedOption FROM `$answersTableName` WHERE userLoginId = ? AND testId = ?";
        $eStmt = $connection->prepare($existingAnswersQuery);
        $existingMap = [];
        if ($eStmt) {
            $eStmt->bind_param("ii", $studentId, $testId);
            $eStmt->execute();
            $eRes = $eStmt->get_result();
            if ($eRes) {
                while ($eRow = $eRes->fetch_assoc()) {
                    $existingMap[(int) $eRow['questionId']] = $eRow['selectedOption'];
                }
            }
            $eStmt->close();
        }

        $updStmt = $connection->prepare("UPDATE `$answersTableName` SET selectedOption = ?, correctOption = ?, markForQuestion = ? WHERE userLoginId = ? AND testId = ? AND questionId = ?");
        $insStmt = $connection->prepare("INSERT INTO `$answersTableName` (userLoginId, testId, questionId, selectedOption, correctOption, markForQuestion) VALUES (?, ?, ?, ?, ?, ?)");

        foreach ($validAnswers as $questionId => $selectedOption) {
            $qRow = $questionsMap[$questionId] ?? null;
            if (!$qRow) {
                continue;
            }

            if ($randomizeOptions === 'Yes') {
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

            $correctOption = dlhsNormalizeOptionValue($qRow['correctOption'] ?? '');
            $markForQuestion = (float) ($qRow['markForQuestion'] ?? 0);

            try {
                if (isset($existingMap[$questionId])) {
                    if ($updStmt) {
                        $updStmt->bind_param("ssdiii", $selectedOption, $correctOption, $markForQuestion, $studentId, $testId, $questionId);
                        $updStmt->execute();
                    }
                } else {
                    if ($insStmt) {
                        $insStmt->bind_param("iiissd", $studentId, $testId, $questionId, $selectedOption, $correctOption, $markForQuestion);
                        $insStmt->execute();
                    }
                }
            } catch (Throwable $e) {
                error_log("Failed to save answer for Q$questionId: " . $e->getMessage());
            }
        }

        if ($updStmt) $updStmt->close();
        if ($insStmt) $insStmt->close();
    }

    try {
        dlhsRepairAnswerMetadata($connection, $answersTableName, $questionsTableName, (int) $studentId, (int) $testId);
    } catch (Throwable $e) {
        error_log("dlhsRepairAnswerMetadata warning: " . $e->getMessage());
    }

    $timeSubmittedTest = date('h:i A');
    $setStatusTo = 2;
    $query1 = "UPDATE `$testTestedTable` SET testStatus ='$setStatusTo', timeSubmittedTest='$timeSubmittedTest' WHERE testId ='$testId' AND examineeUserId ='$studentId'";
    if (!$connection->query($query1)) {
        throw new RuntimeException('Unable to mark the test as submitted: ' . $connection->error);
    }

    if (function_exists('markTestCompleted')) {
        markTestCompleted($testId);
    }

    $query2 = "SELECT SUM(markForQuestion) AS theSum FROM `$questionsTableName`";
    $result2 = $connection->query($query2);
    $row2 = $result2 ? $result2->fetch_assoc() : array();
    $totalToBeEarned = isset($row2['theSum']) ? (float) $row2['theSum'] : 0;

    $query3 = "SELECT SUM(markForQuestion) AS totalEarned FROM `$answersTableName` WHERE UPPER(TRIM(selectedOption))=UPPER(TRIM(correctOption)) AND userLoginId='$studentId' AND testId='$testId'";
    $result3 = $connection->query($query3);
    $row3 = $result3 ? $result3->fetch_assoc() : array();
    $totalEarned = isset($row3['totalEarned']) ? (float) $row3['totalEarned'] : 0;

    $query4 = "SELECT COUNT(*) AS totalQuestions FROM `$questionsTableName`";
    $result4 = $connection->query($query4);
    $row4 = $result4 ? $result4->fetch_assoc() : array();
    $totalQuestions = isset($row4['totalQuestions']) ? (int) $row4['totalQuestions'] : 0;

    $query5 = "SELECT COUNT(*) AS totalCorrect FROM `$answersTableName` WHERE UPPER(TRIM(selectedOption))=UPPER(TRIM(correctOption)) AND userLoginId='$studentId' AND testId='$testId'";
    $result5 = $connection->query($query5);
    $row5 = $result5 ? $result5->fetch_assoc() : array();
    $numberCorrect = isset($row5['totalCorrect']) ? (int) $row5['totalCorrect'] : 0;

    $query6 = "UPDATE `$testTestedTable` SET totalToBeEarned ='$totalToBeEarned', totalEarned='$totalEarned', noOfQuestions='$totalQuestions', noCorrect='$numberCorrect' WHERE testId ='$testId' AND examineeUserId ='$studentId'";
    if (!$connection->query($query6)) {
        throw new RuntimeException('Unable to update the test summary: ' . $connection->error);
    }

    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode(['success' => true, 'message' => 'Objective section submitted successfully.']);
} catch (Throwable $exception) {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Objective submission failed: ' . $exception->getMessage(),
    ]);
}
