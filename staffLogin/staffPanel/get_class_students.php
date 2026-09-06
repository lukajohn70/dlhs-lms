<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['staffLoggedIn']) && !isset($_SESSION['adminLoggedIn'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include "../../db_connection/dlhs_db_connection.php";

try {
    $classId = $_GET['classId'] ?? $_POST['classId'] ?? null;
    
    if (!$classId) {
        echo json_encode(['error' => 'Class ID is required']);
        exit;
    }
    
    // Get students in this class
    $query = "SELECT 
                s.studentId,
                s.admissionNumber,
                s.surname,
                s.firstName,
                s.middleName,
                CONCAT(s.surname, ' ', s.firstName, ' ', COALESCE(s.middleName, '')) as fullName,
                s.gender,
                CASE WHEN s.status = 1 THEN 'Active' ELSE 'Inactive' END as status,
                s.studentEmail,
                s.classId,
                c.className as armName
              FROM studentlogin s
              LEFT JOIN classes c ON s.classId = c.classId
              WHERE s.classId = ? AND s.status = 1
              ORDER BY s.surname, s.firstName";
    
    $stmt = $connection->prepare($query);
    $stmt->bind_param("i", $classId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = [
            'studentId' => $row['studentId'],
            'admissionNumber' => $row['admissionNumber'],
            'surname' => $row['surname'],
            'firstName' => $row['firstName'],
            'middleName' => $row['middleName'],
            'fullName' => trim($row['fullName']),
            'gender' => $row['gender'],
            'status' => $row['status'],
            'studentEmail' => $row['studentEmail'],
            'classId' => $row['classId'],
            'armName' => $row['armName'] ?? 'Unassigned'
        ];
    }
    
    $stmt->close();
    $connection->close();
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($students);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
