<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";

// Check if user is logged in
if (!isset($_SESSION['studentLoggedIn']) || $_SESSION['studentLoggedIn'] !== "yes") {
    echo '<div class="alert alert-danger">Not authorized</div>';
    exit();
}

$studentId = $_SESSION['studentId'];

// Get submissions by this student
$query = "SELECT sub.*, f.title as assignmentTitle, f.maxMarks, f.dueDate,
          c.categoryName, s.subjectName
          FROM assignment_submissions sub
          JOIN file_uploads f ON sub.fileId = f.fileId
          LEFT JOIN file_categories c ON f.categoryId = c.categoryId
          LEFT JOIN subjects s ON f.subjectId = s.subjectId
          WHERE sub.studentId = $studentId
          ORDER BY sub.submittedAt DESC";

$result = $connection->query($query);

if ($result && $result->num_rows > 0) {
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Assignment</th>';
    echo '<th>Subject</th>';
    echo '<th>Submitted</th>';
    echo '<th>Status</th>';
    echo '<th>Grade</th>';
    echo '<th>Feedback</th>';
    echo '<th>Actions</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    while ($row = $result->fetch_assoc()) {
        $submittedAt = date('M d, Y H:i', strtotime($row['submittedAt']));
        $dueDate = $row['dueDate'] ? date('M d, Y H:i', strtotime($row['dueDate'])) : 'No due date';
        $isLate = $row['isLate'] ? '<span class="badge bg-danger">Late</span>' : '';
        
        $statusClass = '';
        $statusText = '';
        
        switch ($row['status']) {
            case 'submitted':
                $statusClass = 'text-warning';
                $statusText = 'Pending Review';
                break;
            case 'graded':
                $statusClass = 'text-success';
                $statusText = 'Graded';
                break;
            case 'returned':
                $statusClass = 'text-info';
                $statusText = 'Returned';
                break;
        }
        
        echo '<tr>';
        echo '<td>';
        echo '<div>';
        echo '<strong>' . htmlspecialchars($row['assignmentTitle']) . '</strong><br>';
        echo '<small class="text-muted">Due: ' . $dueDate . '</small>';
        echo '</div>';
        echo '</td>';
        echo '<td>' . htmlspecialchars($row['subjectName']) . '</td>';
        echo '<td>';
        echo '<div>';
        echo $submittedAt . '<br>';
        echo $isLate;
        echo '</div>';
        echo '</td>';
        echo '<td><span class="' . $statusClass . '">' . $statusText . '</span></td>';
        echo '<td>';
        if ($row['grade'] !== null) {
            echo '<strong>' . $row['grade'] . '</strong>';
            if ($row['maxMarks']) {
                echo ' / ' . $row['maxMarks'];
                $percentage = ($row['grade'] / $row['maxMarks']) * 100;
                echo ' (' . round($percentage, 1) . '%)';
            }
        } else {
            echo '-';
        }
        echo '</td>';
        echo '<td>';
        if ($row['feedback']) {
            echo '<small>' . htmlspecialchars(substr($row['feedback'], 0, 50));
            if (strlen($row['feedback']) > 50) {
                echo '...';
            }
            echo '</small>';
        } else {
            echo '-';
        }
        echo '</td>';
        echo '<td>';
        echo '<div class="btn-group btn-group-sm">';
        echo '<button class="btn btn-outline-primary" onclick="viewSubmission(' . $row['submissionId'] . ')">';
        echo '<i class="fas fa-eye"></i>';
        echo '</button>';
        if ($row['status'] === 'graded' || $row['status'] === 'returned') {
            echo '<button class="btn btn-outline-success" onclick="downloadSubmission(' . $row['submissionId'] . ')">';
            echo '<i class="fas fa-download"></i>';
            echo '</button>';
        }
        echo '</div>';
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
} else {
    echo '<div class="alert alert-info">No submissions found.</div>';
}
?>
