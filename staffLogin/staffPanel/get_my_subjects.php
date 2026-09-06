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
    // Get the logged-in teacher's ID
    $teacherId = $_SESSION['staffId'] ?? null;
    
    if (!$teacherId) {
        echo json_encode(['error' => 'Teacher ID not found in session']);
        exit;
    }
    
    // Get unique subjects assigned to this teacher
    $query = "SELECT DISTINCT 
                s.subjectId,
                s.subjectName
              FROM subject_teacher_assignment sta
              INNER JOIN subjects s ON sta.subjectId = s.subjectId
              WHERE sta.teacherId = ?
              ORDER BY s.subjectName";
    
    $stmt = $connection->prepare($query);
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $subjects = [];
    while ($row = $result->fetch_assoc()) {
        $subjects[] = [
            'subjectId' => $row['subjectId'],
            'subjectName' => $row['subjectName']
        ];
    }
    
    $stmt->close();
    $connection->close();
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($subjects);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
