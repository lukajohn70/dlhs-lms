<?php
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
session_start();
$studentId = isset($_SESSION['studentId']) ? (int)$_SESSION['studentId'] : 0;
session_write_close();

require_once "../../db_connection/dlhs_db_connection.php";

$testId = isset($_POST['testId']) ? (int)$_POST['testId'] : 0;
if ($studentId <= 0 || $testId <= 0) {
    echo json_encode(array('hasAnswer' => false));
    exit;
}

// Check if essay_answers table exists
$tableCheck = $connection->query("SHOW TABLES LIKE 'essay_answers'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    // Get student's previous answer if exists
    $query = "SELECT essayAnswer FROM essay_answers WHERE testId='$testId' AND studentId='$studentId' LIMIT 1";
    $result = $connection->query($query);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode(array(
            'hasAnswer' => true,
            'answer' => $row['essayAnswer']
        ));
    } else {
        echo json_encode(array('hasAnswer' => false));
    }
} else {
    echo json_encode(array('hasAnswer' => false));
}
?>




