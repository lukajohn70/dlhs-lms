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

// Get assignments created by this staff member
$query = "SELECT f.*, c.categoryName, s.subjectName, 
          COUNT(sub.submissionId) as submissionCount,
          COUNT(CASE WHEN sub.status = 'graded' THEN 1 END) as gradedCount
          FROM file_uploads f 
          LEFT JOIN file_categories c ON f.categoryId = c.categoryId 
          LEFT JOIN subjects s ON f.subjectId = s.subjectId 
          LEFT JOIN assignment_submissions sub ON f.fileId = sub.fileId
          WHERE f.uploadedBy = $staffId AND f.isAssignment = 1
          GROUP BY f.fileId
          ORDER BY f.created_at DESC";

$result = $connection->query($query);

if ($result && $result->num_rows > 0) {
    echo '<div class="row">';
    
    while ($row = $result->fetch_assoc()) {
        $dueDate = $row['dueDate'] ? date('M d, Y H:i', strtotime($row['dueDate'])) : 'No due date';
        $isOverdue = $row['dueDate'] && strtotime($row['dueDate']) < time();
        $overdueClass = $isOverdue ? 'text-danger' : '';
        
        echo '<div class="col-md-6 col-lg-4 mb-3">';
        echo '<div class="card file-card">';
        echo '<div class="card-body">';
        echo '<div class="d-flex align-items-start">';
        echo '<i class="fas fa-tasks file-icon text-warning"></i>';
        echo '<div class="flex-grow-1">';
        echo '<h6 class="card-title">' . htmlspecialchars($row['title']) . '</h6>';
        echo '<p class="card-text small text-muted">';
        echo '<strong>Subject:</strong> ' . htmlspecialchars($row['subjectName']) . '<br>';
        echo '<strong>Due Date:</strong> <span class="' . $overdueClass . '">' . $dueDate . '</span><br>';
        echo '<strong>Max Marks:</strong> ' . ($row['maxMarks'] ? $row['maxMarks'] : 'Not specified') . '<br>';
        echo '<strong>Submissions:</strong> ' . $row['submissionCount'] . '<br>';
        echo '<strong>Graded:</strong> ' . $row['gradedCount'] . '/' . $row['submissionCount'] . '<br>';
        echo '<strong>Created:</strong> ' . date('M d, Y H:i', strtotime($row['created_at']));
        echo '</p>';
        
        if ($row['instructions']) {
            echo '<div class="alert alert-light small">';
            echo '<strong>Instructions:</strong><br>';
            echo htmlspecialchars(substr($row['instructions'], 0, 100));
            if (strlen($row['instructions']) > 100) {
                echo '...';
            }
            echo '</div>';
        }
        
        echo '<div class="d-flex justify-content-between align-items-center">';
        echo '<span class="badge bg-warning">Assignment</span>';
        echo '<div class="btn-group btn-group-sm">';
        echo '<button class="btn btn-outline-primary" onclick="viewSubmissions(' . $row['fileId'] . ')">';
        echo '<i class="fas fa-eye"></i> View Submissions';
        echo '</button>';
        echo '<button class="btn btn-outline-success" onclick="downloadFile(' . $row['fileId'] . ', \'' . htmlspecialchars($row['originalName']) . '\')">';
        echo '<i class="fas fa-download"></i>';
        echo '</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    
    echo '</div>';
} else {
    echo '<div class="alert alert-info">No assignments created yet.</div>';
}
?>
