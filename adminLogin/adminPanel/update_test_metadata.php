<?php
session_start();
require_once 'userExpiredSession.php';
if (!isset($_SESSION['adminLoggedIn'])) {
    echo 0;
    exit();
}

include "../../db_connection/dlhs_db_connection.php";

$testId = isset($_POST['testId']) ? intval($_POST['testId']) : 0;
$session = isset($_POST['session']) ? $_POST['session'] : '';
$term = isset($_POST['term']) ? $_POST['term'] : '';
$testType = isset($_POST['testType']) ? $_POST['testType'] : '';

if ($testId == 0) {
    echo "Invalid test ID";
    exit();
}

$updateQuery = "UPDATE tests SET 
                academicSession = '" . $connection->real_escape_string($session) . "',
                term = '" . $connection->real_escape_string($term) . "',
                testType = '" . $connection->real_escape_string($testType) . "'
                WHERE testId = $testId";

if ($connection->query($updateQuery)) {
    echo 1;
} else {
    echo "Error: " . $connection->error;
}
?>
