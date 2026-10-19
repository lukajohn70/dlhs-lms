<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(0);
require_once __DIR__ . '/userExpiredSession.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['adminLoggedIn'])) {
    echo json_encode([]);
    exit();
}

include __DIR__ . '/../../db_connection/dlhs_db_connection.php';

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
    $whereConditions[] = "t.status = '$statusFilter'";
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
    t.essayOption,
    t.essayTime,
    COALESCE(NULLIF(TRIM(CONCAT(COALESCE(st.firstName, ''), ' ', COALESCE(st.surname, ''))), ''), 'Unknown Teacher') as teacherName,
    COALESCE(s.subjectName, 'Unknown Subject') as subjectName,
    COALESCE(y.yearGroupName, 'Unknown Year Group') as yearGroupName
    FROM tests t
    LEFT JOIN stafflogin st ON t.staffId = st.staffId
    LEFT JOIN subjects s ON t.subject = s.subjectId
    LEFT JOIN yeargroup y ON t.yearGroup = y.yearGroupId
    $whereClause
    ORDER BY t.testDate DESC, t.testId DESC";

$result = $connection->query($query);

// Cache all existing tables once to eliminate redundant queries
$allTablesRes = $connection->query("SHOW TABLES");
$existingTables = [];
if ($allTablesRes) {
    while ($tRow = $allTablesRes->fetch_array(MYSQLI_NUM)) {
        $existingTables[strtolower($tRow[0])] = true;
    }
}

$tests = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $questionsTable = trim((string)$row['tableName']);
        $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $questionsTable);
        $questionCount = 0;
        
        if (!empty($safeTable) && isset($existingTables[strtolower($safeTable)])) {
            $countQuery = "SELECT COUNT(*) as count FROM `$safeTable`";
            $countResult = $connection->query($countQuery);
            if ($countResult) {
                $countRow = $countResult->fetch_assoc();
                $questionCount = (int)$countRow['count'];
            }
        }
        
        $row['questionCount'] = $questionCount;
        $tests[] = $row;
    }
}

echo json_encode($tests);
?>
