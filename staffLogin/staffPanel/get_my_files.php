<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";

// Check if user is logged in
if (!isset($_SESSION['staffLoggedIn']) || $_SESSION['staffLoggedIn'] !== "yes") {
    echo '<div class="alert alert-danger">Not authorized</div>';
    exit();
}

$staffId = $_SESSION['staffId'];

// Get files uploaded by this staff member
$query = "SELECT f.*, c.categoryName, s.subjectName 
          FROM file_uploads f 
          LEFT JOIN file_categories c ON f.categoryId = c.categoryId 
          LEFT JOIN subjects s ON f.subjectId = s.subjectId 
          WHERE f.uploadedBy = $staffId 
          ORDER BY f.created_at DESC";

$result = $connection->query($query);

$files = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $files[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'files' => $files
]);
?>
