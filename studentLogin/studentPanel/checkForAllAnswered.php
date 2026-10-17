<?php
session_start();

$answersTableName = $_SESSION['answersTableName'] ?? '';
$studentId = (int)($_SESSION['studentId'] ?? 0);
$totalQuestions = (int)($_SESSION['totalQuestions'] ?? 0);
$questionIdToSave = (int)($_SESSION['nextQuesIdTosave'] ?? 0);

// Release session lock immediately
session_write_close();

if (empty($answersTableName) || $studentId <= 0 || !preg_match('/^[a-zA-Z0-9_]+$/', $answersTableName)) {
    echo 0;
    exit;
}

require_once "../../db_connection/dlhs_db_connection.php";

$query = "SELECT COUNT(*) as cnt FROM `$answersTableName` WHERE userLoginId = '$studentId' AND selectedOption IN ('A','B','C','D','E')";
$result = $connection->query($query);
if (!$result) {
    die($connection->error);
}

$row = $result->fetch_assoc();
$numberOfQuestionsAnweredSoFar = (int)($row['cnt'] ?? 0);

if ($numberOfQuestionsAnweredSoFar > 0) {
    if ($totalQuestions == $numberOfQuestionsAnweredSoFar) {
        echo 1; // All questions have been answered
    } else {
        if (($totalQuestions - $numberOfQuestionsAnweredSoFar) == 1) {
            // Checking if the sent question Id is the only question yet to be answered
            $query1 = "SELECT COUNT(*) as cnt FROM `$answersTableName` WHERE userLoginId = '$studentId' AND questionId = '$questionIdToSave' AND selectedOption IN ('A','B','C','D','E')";
            $result1 = $connection->query($query1);
            $row1 = $result1 ? $result1->fetch_assoc() : null;
            $cnt1 = (int)($row1['cnt'] ?? 0);
            if ($cnt1 > 0) {
                echo 2;
            } else {
                echo 1;
            }
        } else {
            echo 2;
        }
    }
} else {
    echo 2; // Unanswered questions remain
}
?>