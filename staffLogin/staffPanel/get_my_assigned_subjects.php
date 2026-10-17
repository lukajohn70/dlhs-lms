<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['staffLoggedIn']) && !isset($_SESSION['adminLoggedIn'])) {
    session_write_close();
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$teacherId = $_SESSION['staffId'] ?? null;
session_write_close();

include "../../db_connection/dlhs_db_connection.php";

try {
    if (!$teacherId) {
        echo json_encode(['error' => 'Teacher ID not found in session']);
        exit;
    }
    
    // Query assignments along with student count per class in a single query
    $query = "SELECT 
                sta.subjectTeacherAssignmentId,
                s.subjectName,
                s.subjectId,
                CONCAT(yg.yearGroupName, ' - ', c.className) AS className,
                sta.classId,
                COALESCE(sc.studentCount, 0) AS studentCount,
                '' AS academicSession
              FROM subject_teacher_assignment sta
              INNER JOIN subjects s ON sta.subjectId = s.subjectId
              INNER JOIN classes c ON sta.classId = c.classId
              INNER JOIN yeargroup yg ON c.classYearGroup = yg.yearGroupId
              LEFT JOIN (
                  SELECT classId, COUNT(*) AS studentCount
                  FROM studentlogin
                  GROUP BY classId
              ) sc ON sc.classId = sta.classId
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
            'studentCount' => (int)$row['studentCount'],
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
