<?php
session_start();
require_once "../../db_connection/dlhs_db_connection.php";

if (!isset($_SESSION['studentId']) || !isset($_POST['testId'])) {
    echo json_encode(array('hasAnswer' => false));
    exit;
}

$studentId = $connection->real_escape_string($_SESSION['studentId']);
$testId = $connection->real_escape_string($_POST['testId']);

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




