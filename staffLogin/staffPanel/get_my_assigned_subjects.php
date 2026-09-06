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
    
    // Query directly from tables instead of view to get correct data
    $query = "SELECT 
                sta.subjectTeacherAssignmentId,
                s.subjectName,
                s.subjectId,
                CONCAT(yg.yearGroupName, ' - ', c.className) AS className,
                sta.classId,
                '' AS academicSession
              FROM subject_teacher_assignment sta
              INNER JOIN subjects s ON sta.subjectId = s.subjectId
              INNER JOIN classes c ON sta.classId = c.classId
              INNER JOIN yeargroup yg ON c.classYearGroup = yg.yearGroupId
              WHERE sta.teacherId = ?
              ORDER BY s.subjectName, yg.yearGroupName, c.className";
    
    $stmt = $connection->prepare($query);
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $assignments = [];
    while ($row = $result->fetch_assoc()) {
        $assignments[] = [
            'assignmentId' => $row['subjectTeacherAssignmentId'],
            'subjectId' => $row['subjectId'],
            'subjectName' => $row['subjectName'],
            'classId' => $row['classId'],
            'className' => $row['className'],
            'academicSession' => $row['academicSession']
        ];
    }
    
    $stmt->close();
    $connection->close();
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($assignments);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
