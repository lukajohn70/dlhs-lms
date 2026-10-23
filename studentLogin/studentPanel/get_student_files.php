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

$studentId = (int)$_SESSION['studentId'];

// Get student's classId from studentlogin table
$studentQuery = "SELECT classId FROM studentlogin WHERE studentId = $studentId";
$studentResult = $connection->query($studentQuery);
$studentClassId = 0;
if ($studentResult && $studentResult->num_rows > 0) {
    $studentRow = $studentResult->fetch_assoc();
    $studentClassId = intval($studentRow['classId']);
}

// 1. Files shared with this student by Staff or Admin
$query = "SELECT DISTINCT f.*, s.subjectName,
                 CASE 
                     WHEN f.uploaderType = 'admin' THEN 'School Administration'
                     ELSE CONCAT(st.surname, ' ', st.firstName)
                 END as teacherName,
                 f.uploaderType as senderType
          FROM file_uploads f 
          LEFT JOIN subjects s ON f.subjectId = s.subjectId 
          LEFT JOIN stafflogin st ON (f.uploaderType = 'staff' AND f.uploadedBy = st.staffId)
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

// 2. Files submitted by this student to Teachers
$subQuery = "SELECT f.*, s.subjectName,
                    CONCAT(st.surname, ' ', st.firstName) as teacherName,
                    st.username as teacherEmail
             FROM file_uploads f
             LEFT JOIN subjects s ON f.subjectId = s.subjectId
             LEFT JOIN stafflogin st ON f.targetStaffId = st.staffId
             WHERE f.uploadedBy = $studentId 
               AND f.uploaderType = 'student'
               AND f.isActive = 1
             ORDER BY f.created_at DESC";

$subResult = $connection->query($subQuery);
$mySubmissions = [];
if ($subResult && $subResult->num_rows > 0) {
    while ($row = $subResult->fetch_assoc()) {
        $mySubmissions[] = $row;
    }
}

// 3. Teachers assigned to this student's class
$teachers = [];
if ($studentClassId > 0) {
    $teachersQuery = "SELECT DISTINCT s.staffId, CONCAT(s.surname, ' ', s.firstName) as teacherName,
                             sub.subjectId, sub.subjectName, 'subject_teacher' as role
                      FROM subject_teacher_assignment sta
                      INNER JOIN stafflogin s ON sta.teacherId = s.staffId
                      INNER JOIN subjects sub ON sta.subjectId = sub.subjectId
                      WHERE sta.classId = $studentClassId
                      UNION
                      SELECT DISTINCT s.staffId, CONCAT(s.surname, ' ', s.firstName) as teacherName,
                             0 as subjectId, 'Form Teacher / Pastoral' as subjectName, 'form_teacher' as role
                      FROM form_teacher_assignment fta
                      INNER JOIN stafflogin s ON fta.teacherId = s.staffId
                      WHERE fta.classId = $studentClassId
                      ORDER BY subjectName ASC, teacherName ASC";
    $tRes = $connection->query($teachersQuery);
    if ($tRes && $tRes->num_rows > 0) {
        while ($tRow = $tRes->fetch_assoc()) {
            $teachers[] = $tRow;
        }
    }
}

echo json_encode([
    'success'       => true,
    'files'         => $files,
    'mySubmissions' => $mySubmissions,
    'teachers'      => $teachers,
    'stats'         => [
        'totalReceived'    => count($files),
        'totalSubmissions' => count($mySubmissions)
    ]
]);
?>
