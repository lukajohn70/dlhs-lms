<?php
ob_start(); // Start output buffering to catch any stray output
error_reporting(0); // Suppress warnings that might break JSON
ini_set('display_errors', 0); // Disable error display
session_start();
require_once 'userExpiredSession.php';
if (!isset($_SESSION['adminLoggedIn'])) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

// Include connection but suppress error output
$oldErrorReporting = error_reporting(0);
$oldDisplayErrors = ini_get('display_errors');
ini_set('display_errors', 0);
include "../../db_connection/dlhs_db_connection.php";
error_reporting($oldErrorReporting);
ini_set('display_errors', $oldDisplayErrors);

// Get filter parameters
$session = isset($_POST['session']) ? $_POST['session'] : '';
$term = isset($_POST['term']) ? $_POST['term'] : '';
$testType = isset($_POST['testType']) ? $_POST['testType'] : '';
$status = isset($_POST['status']) ? $_POST['status'] : 'active';

// Build query
$query = "SELECT t.*, s.subjectName, y.yearGroupName 
          FROM tests t
          LEFT JOIN subjects s ON t.subjectId = s.subjectId
          LEFT JOIN yeargroup y ON t.yearGroup = y.yearGroupId
          WHERE 1=1";

// Filter by archive status
if ($status === 'active') {
    $query .= " AND (t.isArchived = 0 OR t.isArchived IS NULL)";
} elseif ($status === 'archived') {
    $query .= " AND t.isArchived = 1";
}

// Filter by session
if (!empty($session)) {
    $query .= " AND t.academicSession = '" . $connection->real_escape_string($session) . "'";
}

// Filter by term
if (!empty($term)) {
    $query .= " AND t.term = '" . $connection->real_escape_string($term) . "'";
}

// Filter by test type
if (!empty($testType)) {
    $query .= " AND t.testType = '" . $connection->real_escape_string($testType) . "'";
}

$query .= " ORDER BY t.testId DESC";

$result = $connection->query($query);
$return_arr = array();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $return_arr[] = array(
            "testId" => $row['testId'],
            "testName" => $row['testName'],
            "testDate" => $row['testDate'],
            "subjectName" => $row['subjectName'] ? $row['subjectName'] : 'N/A',
            "yearGroupName" => $row['yearGroupName'] ? $row['yearGroupName'] : 'N/A',
            "academicSession" => $row['academicSession'] ? $row['academicSession'] : 'Not Set',
            "term" => $row['term'] ? $row['term'] : 'Not Set',
            "testType" => $row['testType'] ? $row['testType'] : 'Other',
            "status" => $row['status'],
            "isArchived" => $row['isArchived'] ? $row['isArchived'] : 0
        );
    }
}

// Clear any output buffer
ob_end_clean();

header('Content-Type: application/json');
echo json_encode($return_arr);
?>
