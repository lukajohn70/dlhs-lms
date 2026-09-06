<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/file_assignment_helper.php";

// Ensure all DB tables are checked/created
dlhsEnsureAllFileManagementTablesExist($connection);

// Check if user is logged in
if (!isset($_SESSION['studentLoggedIn']) || $_SESSION['studentLoggedIn'] !== "yes") {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

$studentId = $_SESSION['studentId'];

// Get student's classId from studentlogin table
$studentQuery = "SELECT classId FROM studentlogin WHERE studentId = '$studentId'";
$studentResult = $connection->query($studentQuery);
$studentClassId = 0;
if ($studentResult && $studentResult->num_rows > 0) {
    $studentRow = $studentResult->fetch_assoc();
    $studentClassId = intval($studentRow['classId']);
}

// Get files shared with this student
// 1. Shared with all students
// 2. Shared with student's class
// 3. Shared with student specifically (via file_student_assignments)
// In all cases, teacher must have explicitly published the file (isPublishedToStudents = 1)
$query = "SELECT DISTINCT f.*, s.subjectName, st.surname as teacherSurname, st.firstName as teacherFirstName
          FROM file_uploads f 
          LEFT JOIN subjects s ON f.subjectId = s.subjectId 
          LEFT JOIN stafflogin st ON f.uploadedBy = st.staffId
          LEFT JOIN file_student_assignments fsa ON f.fileId = fsa.fileId
          WHERE f.isActive = 1
          AND f.isPublishedToStudents = 1
          AND (
              f.uploadedFor = 'all' 
              OR (f.uploadedFor = 'specific_class' AND f.targetClassId = $studentClassId)
              OR (f.uploadedFor = 'specific_students' AND fsa.studentId = $studentId)
          )
          ORDER BY f.created_at DESC";

$result = $connection->query($query);

$files = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $files[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'files' => $files
]);
?>
