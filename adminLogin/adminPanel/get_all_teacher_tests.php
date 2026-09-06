<?php
session_start();
	error_reporting(0);
require_once 'userExpiredSession.php';

if (!isset($_SESSION['adminLoggedIn'])) {
    echo json_encode([]);
    exit();
}

include "../../db_connection/dlhs_db_connection.php";

// Get filter parameters
$teacherFilter = isset($_POST['teacher']) && $_POST['teacher'] !== '' ? intval($_POST['teacher']) : null;
$subjectFilter = isset($_POST['subject']) && $_POST['subject'] !== '' ? intval($_POST['subject']) : null;
$yearGroupFilter = isset($_POST['yearGroup']) && $_POST['yearGroup'] !== '' ? intval($_POST['yearGroup']) : null;
$statusFilter = isset($_POST['status']) && $_POST['status'] !== '' ? intval($_POST['status']) : null;

// Build WHERE clause
$whereConditions = [];
if ($teacherFilter !== null) {
    $whereConditions[] = "t.staffId = $teacherFilter";
}
if ($subjectFilter !== null) {
    $whereConditions[] = "t.subject = $subjectFilter";
}
if ($yearGroupFilter !== null) {
    $whereConditions[] = "t.yearGroup = $yearGroupFilter";
}
if ($statusFilter !== null) {
    $whereConditions[] = "t.status = $statusFilter";
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Query to get all tests with related information
$query = "SELECT 
    t.testId,
    t.testName,
    t.testDate,
    t.duration,
    t.status,
    t.tableName,
    CONCAT(st.firstName, ' ', st.surname) as teacherName,
    s.subjectName,
    y.yearGroupName
    FROM tests t
    LEFT JOIN stafflogin st ON t.staffId = st.staffId
    LEFT JOIN subjects s ON t.subject = s.subjectId
    LEFT JOIN yeargroup y ON t.yearGroup = y.yearGroupId
    $whereClause
    ORDER BY t.testDate DESC, t.testId DESC";

$result = $connection->query($query);

$tests = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Get question count from the questions table
        $questionsTable = $row['tableName'];
        $questionCount = 0;
        
        if (!empty($questionsTable)) {
            // Check if table exists
            $checkTable = $connection->query("SHOW TABLES LIKE '$questionsTable'");
            if ($checkTable && $checkTable->num_rows > 0) {
                $countQuery = "SELECT COUNT(*) as count FROM `$questionsTable`";
                $countResult = $connection->query($countQuery);
                if ($countResult) {
                    $countRow = $countResult->fetch_assoc();
                    $questionCount = $countRow['count'];
                }
            }
        }
        
        $row['questionCount'] = $questionCount;
        $tests[] = $row;
    }
}

echo json_encode($tests);
?>

