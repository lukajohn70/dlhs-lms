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

// Get submissions for assignments created by this staff member
$query = "SELECT sub.*, f.title as assignmentTitle, f.maxMarks, f.dueDate,
          s.surname, s.firstName, s.middleName, s.studentEmail,
          c.categoryName, subj.subjectName
          FROM assignment_submissions sub
          JOIN file_uploads f ON sub.fileId = f.fileId
          JOIN studentlogin s ON sub.studentId = s.studentId
          LEFT JOIN file_categories c ON f.categoryId = c.categoryId
          LEFT JOIN subjects subj ON f.subjectId = subj.subjectId
          WHERE f.uploadedBy = $staffId AND f.isAssignment = 1
          ORDER BY sub.submittedAt DESC";

$result = $connection->query($query);

if ($result && $result->num_rows > 0) {
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Student</th>';
    echo '<th>Assignment</th>';
    echo '<th>Subject</th>';
    echo '<th>Submitted</th>';
    echo '<th>Status</th>';
    echo '<th>Grade</th>';
    echo '<th>Actions</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    while ($row = $result->fetch_assoc()) {
        $studentName = htmlspecialchars($row['surname'] . ' ' . $row['firstName'] . ' ' . $row['middleName']);
        $submittedAt = date('M d, Y H:i', strtotime($row['submittedAt']));
        $isLate = $row['isLate'] ? '<span class="badge bg-danger">Late</span>' : '';
        $statusClass = '';
        $statusText = '';
        
        switch ($row['status']) {
            case 'submitted':
                $statusClass = 'text-warning';
                $statusText = 'Pending';
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
        echo '<strong>' . $studentName . '</strong><br>';
        echo '<small class="text-muted">' . htmlspecialchars($row['studentEmail']) . '</small>';
        echo '</div>';
        echo '</td>';
        echo '<td>';
        echo '<div>';
        echo '<strong>' . htmlspecialchars($row['assignmentTitle']) . '</strong><br>';
        echo '<small class="text-muted">Max: ' . ($row['maxMarks'] ? $row['maxMarks'] : 'N/A') . ' marks</small>';
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
            }
        } else {
            echo '-';
        }
        echo '</td>';
        echo '<td>';
        echo '<div class="btn-group btn-group-sm">';
        echo '<button class="btn btn-outline-primary" onclick="viewSubmission(' . $row['submissionId'] . ')">';
        echo '<i class="fas fa-eye"></i>';
        echo '</button>';
        if ($row['status'] !== 'graded') {
            echo '<button class="btn btn-outline-success" onclick="gradeSubmission(' . $row['submissionId'] . ')">';
            echo '<i class="fas fa-check"></i> Grade';
            echo '</button>';
        } else {
            echo '<button class="btn btn-outline-warning" onclick="editGrade(' . $row['submissionId'] . ')">';
            echo '<i class="fas fa-edit"></i> Edit';
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
    echo '<div class="alert alert-info">No submissions to grade.</div>';
}
?>
