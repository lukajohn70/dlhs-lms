<?php
session_start();
include "../../db_connection/dlhs_db_connection.php";

if (!isset($_SESSION['staffLoggedIn'])) {
    echo json_encode(array('success' => false, 'message' => 'Unauthorized'));
    exit;
}

$staffId = (int) $_SESSION['staffId'];
$type = isset($_GET['type']) ? $_GET['type'] : '';
$yearGroupId = isset($_GET['yearGroupId']) ? (int) $_GET['yearGroupId'] : 0;
$classId = isset($_GET['classId']) ? (int) $_GET['classId'] : 0;

$response = array('success' => true, 'data' => array());

if ($type === 'classes' && $yearGroupId > 0) {
    // Fetch classes in this year group assigned to the teacher
    $query = "SELECT DISTINCT c.classId, c.className 
              FROM classes c 
              INNER JOIN subject_teacher_assignment sta ON c.classId = sta.classId 
              WHERE c.classYearGroup = '$yearGroupId' 
              AND sta.teacherId = '$staffId' 
              ORDER BY c.className ASC";
    $result = $connection->query($query);
    while ($row = $result->fetch_assoc()) {
        $response['data'][] = $row;
    }
} elseif ($type === 'subjects' && $classId > 0) {
    // Fetch subjects assigned to the teacher for this class
    $query = "SELECT DISTINCT s.subjectId, s.subjectName 
              FROM subjects s 
              INNER JOIN subject_teacher_assignment sta ON s.subjectId = sta.subjectId 
              WHERE sta.classId = '$classId' 
              AND sta.teacherId = '$staffId' 
              ORDER BY s.subjectName ASC";
    $result = $connection->query($query);
    while ($row = $result->fetch_assoc()) {
        $response['data'][] = $row;
    }
} elseif ($type === 'findClass' && $yearGroupId > 0 && isset($_GET['subjectId'])) {
    $subjectId = (int) $_GET['subjectId'];
    // Find a class in this year group where the teacher teaches this subject
    $query = "SELECT DISTINCT sta.classId 
              FROM subject_teacher_assignment sta 
              INNER JOIN classes c ON sta.classId = c.classId 
              WHERE c.classYearGroup = '$yearGroupId' 
              AND sta.teacherId = '$staffId' 
              AND sta.subjectId = '$subjectId' 
              LIMIT 1";
    $result = $connection->query($query);
    if ($row = $result->fetch_assoc()) {
        $response['data'] = $row['classId'];
    }
} elseif ($type === 'classesBySubject' && isset($_GET['subjectId']) && isset($_GET['yearGroupId'])) {
    $subjectId = (int) $_GET['subjectId'];
    $yearGroupId = (int) $_GET['yearGroupId'];
    // Fetch classes for this subject and year group assigned to the teacher
    $query = "SELECT DISTINCT c.classId, c.className 
              FROM classes c 
              INNER JOIN subject_teacher_assignment sta ON c.classId = sta.classId 
              WHERE c.classYearGroup = '$yearGroupId' 
              AND sta.subjectId = '$subjectId' 
              AND sta.teacherId = '$staffId' 
              ORDER BY c.className ASC";
    $result = $connection->query($query);
    while ($row = $result->fetch_assoc()) {
        $response['data'][] = $row;
    }
}

echo json_encode($response);
?>
