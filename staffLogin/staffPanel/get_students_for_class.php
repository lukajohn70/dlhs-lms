<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";

// Check if user is logged in
if (!isset($_SESSION['staffLoggedIn']) || $_SESSION['staffLoggedIn'] !== "yes") {
    echo json_encode([]);
    exit();
}

if (isset($_POST['classId'])) {
    $classId = intval($_POST['classId']);
    
    // Get students from the selected class
    $query = "SELECT studentId, surname, firstName, middleName FROM studentlogin WHERE classId = '$classId' ORDER BY surname, firstName";
    $result = $connection->query($query);
    
    $students = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $students[] = [
                'studentId' => $row['studentId'],
                'surname' => $row['surname'],
                'firstName' => $row['firstName'],
                'middleName' => $row['middleName']
            ];
        }
    }
    
    echo json_encode($students);
} else {
    echo json_encode([]);
}

$connection->close();
?>
