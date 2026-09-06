<?php
session_start();
require_once 'userExpiredSession.php';

if (!isset($_SESSION['adminLoggedIn']))
{	
    echo 0;
    exit;
}

include "../../db_connection/dlhs_db_connection.php";

$assignmentId = $_POST['assignmentId'] ?? null;
$classId = $_POST['classId'] ?? null;
$subjectId = $_POST['subjectId'] ?? null;
$teacherId = $_POST['teacherId'] ?? null;

// Validate inputs
if (!$assignmentId || !$classId || !$subjectId || !$teacherId) {
    echo 2;
    exit;
}

// Check if the new combination already exists (except for the current assignment)
$checkQuery = "SELECT subjectTeacherAssignmentId 
               FROM subject_teacher_assignment 
               WHERE classId = ? 
               AND subjectId = ? 
               AND teacherId = ? 
               AND subjectTeacherAssignmentId != ?";

$checkStmt = $connection->prepare($checkQuery);
$checkStmt->bind_param("iiii", $classId, $subjectId, $teacherId, $assignmentId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    // Assignment already exists
    echo 3;
    $checkStmt->close();
    $connection->close();
    exit;
}
$checkStmt->close();

// Update the assignment
$updateQuery = "UPDATE subject_teacher_assignment 
                SET classId = ?, 
                    subjectId = ?, 
                    teacherId = ?
                WHERE subjectTeacherAssignmentId = ?";

$stmt = $connection->prepare($updateQuery);
$stmt->bind_param("iiii", $classId, $subjectId, $teacherId, $assignmentId);

if ($stmt->execute()) {
    echo 1; // Success
} else {
    echo 2; // Error
}

$stmt->close();
$connection->close();
?>
