<?php
session_start();
include "../../db_connection/dlhs_db_connection.php";

if (!isset($_SESSION['staffLoggedIn']) && !isset($_SESSION['adminLoggedIn'])) {
    echo json_encode(array('success' => false, 'message' => 'Unauthorized'));
    exit;
}

$isAdmin = isset($_SESSION['adminLoggedIn']);
$staffId = isset($_SESSION['staffId']) ? (int) $_SESSION['staffId'] : 0;
$type = isset($_GET['type']) ? $_GET['type'] : '';
$yearGroupId = isset($_GET['yearGroupId']) ? (int) $_GET['yearGroupId'] : 0;
$classId = isset($_GET['classId']) ? (int) $_GET['classId'] : 0;

$response = array('success' => true, 'data' => array());

if ($type === 'classes' && $yearGroupId > 0) {
    if ($isAdmin) {
        $query = "SELECT DISTINCT c.classId, c.className 
                  FROM classes c 
                  WHERE c.classYearGroup = '$yearGroupId' 
                  ORDER BY c.className ASC";
    } else {
        $query = "SELECT DISTINCT c.classId, c.className 
                  FROM classes c 
                  INNER JOIN subject_teacher_assignment sta ON c.classId = sta.classId 
                  WHERE c.classYearGroup = '$yearGroupId' 
                  AND sta.teacherId = '$staffId' 
                  ORDER BY c.className ASC";
    }
    $result = $connection->query($query);
    while ($row = $result->fetch_assoc()) {
        $response['data'][] = $row;
    }
} elseif ($type === 'subjects' && $classId > 0) {
    if ($isAdmin) {
        $query = "SELECT DISTINCT s.subjectId, s.subjectName 
                  FROM subjects s 
                  ORDER BY s.subjectName ASC";
    } else {
        $query = "SELECT DISTINCT s.subjectId, s.subjectName 
                  FROM subjects s 
                  INNER JOIN subject_teacher_assignment sta ON s.subjectId = sta.subjectId 
                  WHERE sta.classId = '$classId' 
                  AND sta.teacherId = '$staffId' 
                  ORDER BY s.subjectName ASC";
    }
    $result = $connection->query($query);
    while ($row = $result->fetch_assoc()) {
        $response['data'][] = $row;
    }
} elseif ($type === 'findClass' && $yearGroupId > 0 && isset($_GET['subjectId'])) {
    $subjectId = (int) $_GET['subjectId'];
    if ($isAdmin) {
        $query = "SELECT DISTINCT c.classId 
                  FROM classes c 
                  WHERE c.classYearGroup = '$yearGroupId' 
                  LIMIT 1";
    } else {
        $query = "SELECT DISTINCT sta.classId 
                  FROM subject_teacher_assignment sta 
                  INNER JOIN classes c ON sta.classId = c.classId 
                  WHERE c.classYearGroup = '$yearGroupId' 
                  AND sta.teacherId = '$staffId' 
                  AND sta.subjectId = '$subjectId' 
                  LIMIT 1";
    }
    $result = $connection->query($query);
    if ($row = $result->fetch_assoc()) {
        $response['data'] = $row['classId'];
    }
} elseif ($type === 'classesBySubject' && isset($_GET['subjectId']) && isset($_GET['yearGroupId'])) {
    $subjectId = (int) $_GET['subjectId'];
    $yearGroupId = (int) $_GET['yearGroupId'];
    if ($isAdmin) {
        $query = "SELECT DISTINCT c.classId, c.className 
                  FROM classes c 
                  WHERE c.classYearGroup = '$yearGroupId' 
                  ORDER BY c.className ASC";
    } else {
        $query = "SELECT DISTINCT c.classId, c.className 
                  FROM classes c 
                  INNER JOIN subject_teacher_assignment sta ON c.classId = sta.classId 
                  WHERE c.classYearGroup = '$yearGroupId' 
                  AND sta.subjectId = '$subjectId' 
                  AND sta.teacherId = '$staffId' 
                  ORDER BY c.className ASC";
    }
    $result = $connection->query($query);
    while ($row = $result->fetch_assoc()) {
        $response['data'][] = $row;
    }
}

echo json_encode($response);
?>
