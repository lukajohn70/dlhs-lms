<?php
// Check if a student has submitted the essay for a test (AJAX endpoint)
// Usage: POST testId, studentId

ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
session_start();
$testId = isset($_POST['testId']) ? intval($_POST['testId']) : 0;
$studentId = isset($_POST['studentId']) ? intval($_POST['studentId']) : (isset($_SESSION['studentId']) ? intval($_SESSION['studentId']) : 0);
session_write_close();

require_once '../../db_connection/dlhs_db_connection.php';

$hasEssay = false;
if ($testId && $studentId) {
    $query = "SELECT essayAnswer FROM essay_answers WHERE testId='$testId' AND studentId='$studentId' LIMIT 1";
    $result = $connection->query($query);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (!empty($row['essayAnswer'])) {
            $hasEssay = true;
        }
    }
}

echo json_encode(['hasEssay' => $hasEssay]);
