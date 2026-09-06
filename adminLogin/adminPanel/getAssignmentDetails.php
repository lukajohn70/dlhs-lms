<?php
session_start();
	error_reporting(0);
header('Content-Type: application/json');

if (!isset($_SESSION['adminLoggedIn']))
{	
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

include "../../db_connection/dlhs_db_connection.php";

$assignmentId = $_POST['assignmentId'] ?? null;

if (!$assignmentId) {
    echo json_encode(['error' => 'Assignment ID is required']);
    exit;
}

$query = "SELECT sta.subjectTeacherAssignmentId, 
                 sta.classId,
                 sta.subjectId,
                 sta.teacherId,
                 c.classYearGroup as yearGroupId
          FROM subject_teacher_assignment sta
          JOIN classes c ON sta.classId = c.classId
          WHERE sta.subjectTeacherAssignmentId = ?";

$stmt = $connection->prepare($query);
$stmt->bind_param("i", $assignmentId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        'assignmentId' => $row['subjectTeacherAssignmentId'],
        'classId' => $row['classId'],
        'subjectId' => $row['subjectId'],
        'teacherId' => $row['teacherId'],
        'yearGroupId' => $row['yearGroupId']
    ]);
} else {
    echo json_encode(['error' => 'Assignment not found']);
}

$stmt->close();
$connection->close();
?>
