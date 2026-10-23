<?php
session_start();
include "../../db_connection/dlhs_db_connection.php";

if (!isset($_SESSION['adminLoggedIn'])) {
    echo json_encode(array('success' => false, 'message' => 'Unauthorized'));
    exit;
}

$type = isset($_GET['type']) ? $_GET['type'] : '';
$yearGroupId = isset($_GET['yearGroupId']) ? (int) $_GET['yearGroupId'] : 0;
$classId = isset($_GET['classId']) ? (int) $_GET['classId'] : 0;

$response = array('success' => true, 'data' => array());

if (($type === 'classes' || $type === 'classesBySubject') && $yearGroupId > 0) {
    $query = "SELECT DISTINCT c.classId, c.className 
              FROM classes c 
              WHERE c.classYearGroup = '$yearGroupId' 
              ORDER BY c.className ASC";
    $result = $connection->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $response['data'][] = $row;
        }
    }
} elseif ($type === 'subjects' && $classId > 0) {
    $query = "SELECT DISTINCT s.subjectId, s.subjectName 
              FROM subjects s 
              ORDER BY s.subjectName ASC";
    $result = $connection->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $response['data'][] = $row;
        }
    }
} elseif ($type === 'findClass' && $yearGroupId > 0) {
    $query = "SELECT DISTINCT c.classId 
              FROM classes c 
              WHERE c.classYearGroup = '$yearGroupId' 
              LIMIT 1";
    $result = $connection->query($query);
    if ($result && ($row = $result->fetch_assoc())) {
        $response['data'] = $row['classId'];
    }
}

echo json_encode($response);
?>
