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
    $classId = $_GET['classId'] ?? null;
    
    if (!$classId) {
        echo json_encode(['error' => 'Class ID is required']);
        exit;
    }
    
    // Count students in this class
    $query = "SELECT COUNT(*) as count 
              FROM studentlogin 
              WHERE classId = ?";
    
    $stmt = $connection->prepare($query);
    $stmt->bind_param("i", $classId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $stmt->close();
    $connection->close();
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode(['count' => (int)$row['count']]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
