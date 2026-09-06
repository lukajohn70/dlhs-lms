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
if ($testId === '') { echo 2; exit; }

// Verify ownership and fetch table names
$query = "SELECT * FROM tests WHERE testId='$testId' AND staffId='$staffId'";
$result = $connection->query($query);
if (!$result || $result->num_rows === 0) { echo 2; exit; }
$row = $result->fetch_array(MYSQLI_NUM);
$examineesTableName = $row[14];
$answersTableName = $row[16];

// Delete all answers and examinees rows for this test
$ok1 = $connection->query("DELETE FROM $answersTableName");
$ok2 = $connection->query("DELETE FROM $examineesTableName");

if ($ok1 && $ok2) { echo 1; } else { echo 2; }
?>






