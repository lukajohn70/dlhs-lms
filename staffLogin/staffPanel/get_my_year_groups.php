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
    $subjectId = $_GET['subjectId'] ?? null;
    
    if (!$teacherId) {
        echo json_encode(['error' => 'Teacher ID not found in session']);
        exit;
    }
    
    // Get unique year groups for the teacher's assignments
    if ($subjectId) {
        $query = "SELECT DISTINCT 
                    yg.yearGroupId,
                    yg.yearGroupName
                  FROM subject_teacher_assignment sta
                  INNER JOIN classes c ON sta.classId = c.classId
                  INNER JOIN yeargroup yg ON c.classYearGroup = yg.yearGroupId
                  WHERE sta.teacherId = ? AND sta.subjectId = ?
                  ORDER BY yg.yearGroupName";
        
        $stmt = $connection->prepare($query);
        $stmt->bind_param("ii", $teacherId, $subjectId);
    } else {
        $query = "SELECT DISTINCT 
                    yg.yearGroupId,
                    yg.yearGroupName
                  FROM subject_teacher_assignment sta
                  INNER JOIN classes c ON sta.classId = c.classId
                  INNER JOIN yeargroup yg ON c.classYearGroup = yg.yearGroupId
                  WHERE sta.teacherId = ?
                  ORDER BY yg.yearGroupName";
        
        $stmt = $connection->prepare($query);
        $stmt->bind_param("i", $teacherId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $yearGroups = [];
    while ($row = $result->fetch_assoc()) {
        $yearGroups[] = [
            'yearGroupId' => $row['yearGroupId'],
            'yearGroupName' => $row['yearGroupName']
        ];
    }
    
    $stmt->close();
    $connection->close();
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($yearGroups);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
