<?php
session_start();
    require_once 'sessionTime.php';
    if ((time() - $_SESSION['staffLast_login'])> $allottedTime)
    {
        require_once 'unsetSessions.php';
        echo 0; // session expired
        exit;
    }

include "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/question_authoring_helper.php";

if (!isset($_SESSION['staffId'])) { echo 0; exit; }

// Expect testId and questionIds[]
$staffId = $_SESSION['staffId'];
$testId = isset($_POST['testId']) ? mysqli_real_escape_string($connection, $_POST['testId']) : '';
$questionIds = isset($_POST['questionIds']) ? $_POST['questionIds'] : [];

if ($testId === '' || !is_array($questionIds) || count($questionIds) === 0) { echo 2; exit; }

// Verify ownership and fetch table names
$query = "SELECT * FROM tests WHERE testId='$testId' AND staffId='$staffId'";
$result = $connection->query($query);
if (!$result || $result->num_rows === 0) { echo 2; exit; }
$row = $result->fetch_array(MYSQLI_NUM);
$questionsTableName = $row[13];
$examineesTableName = $row[14];

// Block if any examinee started or completed
$testStatus1 = 1; $testStatus2 = 2;
$query1 = "SELECT 1 FROM $examineesTableName WHERE testStatus='$testStatus1' OR testStatus='$testStatus2' LIMIT 1";
$result1 = $connection->query($query1);
if ($result1 && $result1->num_rows > 0) { echo 3; exit; }

// Sanitize question IDs and delete
$ids = array_map(function($id){ return (int)$id; }, $questionIds);
if (count($ids) === 0) { echo 2; exit; }
$idsList = implode(',', $ids);

$deleteSql = "DELETE FROM $questionsTableName WHERE questionId IN ($idsList)";
$ok = $connection->query($deleteSql);
if ($ok) {
    dlhsDeleteStimulusLinksForQuestions($connection, $testId, $ids);
    echo 1;
} else {
    echo 2;
}
?>






