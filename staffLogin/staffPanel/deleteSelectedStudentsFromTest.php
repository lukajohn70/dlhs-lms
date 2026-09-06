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

if (!isset($_SESSION['staffId'])) { echo 0; exit; }

$staffId = $_SESSION['staffId'];
$testId = isset($_POST['testId']) ? mysqli_real_escape_string($connection, $_POST['testId']) : '';
$studentIds = isset($_POST['studentIds']) ? $_POST['studentIds'] : [];
if ($testId === '' || !is_array($studentIds) || count($studentIds) === 0) { echo 2; exit; }

// Verify ownership and fetch table names
$query = "SELECT * FROM tests WHERE testId='$testId' AND staffId='$staffId'";
$result = $connection->query($query);
if (!$result || $result->num_rows === 0) { echo 2; exit; }
$row = $result->fetch_array(MYSQLI_NUM);
$examineesTableName = $row[14];
$answersTableName = $row[16];

// Sanitize ids
$ids = array_map(function($id){ return (int)$id; }, $studentIds);
if (count($ids) === 0) { echo 2; exit; }
$idsList = implode(',', $ids);

// Delete answers, essays, then examinees for selected students
$ok1 = $connection->query("DELETE FROM $answersTableName WHERE userLoginId IN ($idsList)");
$okEssay = $connection->query("DELETE FROM essay_answers WHERE testId='$testId' AND studentId IN ($idsList)");
$deletedEssays = $connection->affected_rows;
$ok2 = $connection->query("DELETE FROM $examineesTableName WHERE examineeUserId IN ($idsList)");

// For debugging: output how many essay records were deleted
if ($ok1 && $ok2 && $okEssay !== false) {
    echo json_encode(["success" => 1, "deletedEssays" => $deletedEssays]);
} else {
    echo json_encode(["success" => 2, "deletedEssays" => $deletedEssays]);
}
?>






