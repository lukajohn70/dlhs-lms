<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/file_assignment_helper.php";

// Check if user is logged in
if (!isset($_SESSION['studentLoggedIn']) || $_SESSION['studentLoggedIn'] !== "yes") {
    echo '<div class="alert alert-danger">Not authorized</div>';
    exit();
}

$studentId = $_SESSION['studentId'];
dlhsEnsureFileStudentAssignmentsTable($connection);

// Get student's classId from database
$studentQuery = "SELECT classId FROM studentlogin WHERE studentId = '$studentId'";
$studentResult = $connection->query($studentQuery);
$studentRow = $studentResult->fetch_assoc();
$studentClassId = $studentRow['classId'];

// Get assignments available to this student
$query = "SELECT DISTINCT f.*, c.categoryName, s.subjectName, 
          sub.submissionId, sub.status as submissionStatus, sub.grade, sub.submittedAt,
          sub.isLate as submissionLate
          FROM file_uploads f 
          LEFT JOIN file_categories c ON f.categoryId = c.categoryId 
          LEFT JOIN subjects s ON f.subjectId = s.subjectId 
          LEFT JOIN assignment_submissions sub ON f.fileId = sub.fileId AND sub.studentId = $studentId
          LEFT JOIN file_student_assignments fsa ON f.fileId = fsa.fileId
          WHERE f.isAssignment = 1 AND f.isActive = 1 
          AND (f.uploadedFor = 'all' OR f.targetClassId = $studentClassId OR fsa.studentId = $studentId)
          ORDER BY f.dueDate ASC, f.created_at DESC";

$result = $connection->query($query);

if ($result && $result->num_rows > 0) {
    echo '<div class="row">';
    
    while ($row = $result->fetch_assoc()) {
        $dueDate = $row['dueDate'] ? date('M d, Y H:i', strtotime($row['dueDate'])) : 'No due date';
        $isOverdue = $row['dueDate'] && strtotime($row['dueDate']) < time();
        $isSubmitted = $row['submissionId'] !== null;
        $isLate = $isSubmitted && $row['submissionLate'];
        
        // Determine card styling based on status
        $panelClass = 'panel';
        $panelHeaderClass = 'panel-heading';
        $panelBodyClass = 'panel-body';
        
        if ($isOverdue && !$isSubmitted) {
            $panelClass .= ' panel-danger';
            $panelHeaderClass .= ' bg-danger';
        } elseif ($isSubmitted) {
            $panelClass .= ' panel-success';
            $panelHeaderClass .= ' bg-success';
        } else {
            $panelClass .= ' panel-primary';
            $panelHeaderClass .= ' bg-primary';
        }
        
        echo '<div class="col-md-6 col-lg-4" style="margin-bottom: 20px;">';
        echo '<div class="' . $panelClass . '">';
        
        // Panel Header with Assignment Title
        echo '<div class="' . $panelHeaderClass . '" style="color: white; padding: 15px;">';
        echo '<div style="display: flex; align-items: center;">';
        echo '<i class="icon_document_alt" style="margin-right: 10px; font-size: 18px;"></i>';
        echo '<h4 style="margin: 0; font-size: 16px; font-weight: 600;">' . htmlspecialchars($row['title']) . '</h4>';
        echo '</div>';
        echo '</div>';
        
        // Panel Body
        echo '<div class="' . $panelBodyClass . '" style="padding: 20px;">';
        
        // Assignment Details
        echo '<div style="margin-bottom: 15px;">';
        echo '<p style="margin: 5px 0; font-size: 14px;"><strong><i class="icon_book"></i> Subject:</strong> ' . htmlspecialchars($row['subjectName']) . '</p>';
        echo '<p style="margin: 5px 0; font-size: 14px;"><strong><i class="icon_calendar"></i> Due Date:</strong> ';
        if ($isOverdue && !$isSubmitted) {
            echo '<span style="color: #d9534f; font-weight: bold;">' . $dueDate . ' (OVERDUE)</span>';
        } else {
            echo $dueDate;
        }
        echo '</p>';
        echo '<p style="margin: 5px 0; font-size: 14px;"><strong><i class="icon_star"></i> Max Marks:</strong> ' . ($row['maxMarks'] ? $row['maxMarks'] : 'Not specified') . '</p>';
        echo '<p style="margin: 5px 0; font-size: 14px;"><strong><i class="icon_clock"></i> Created:</strong> ' . date('M d, Y H:i', strtotime($row['created_at'])) . '</p>';
        echo '</div>';
        
        // Instructions
        if ($row['instructions']) {
            echo '<div style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin-bottom: 15px; border-left: 4px solid #688a7e;">';
            echo '<strong style="color: #688a7e;"><i class="icon_info"></i> Instructions:</strong><br>';
            echo '<span style="font-size: 13px; color: #666;">';
            echo htmlspecialchars(substr($row['instructions'], 0, 150));
            if (strlen($row['instructions']) > 150) {
                echo '...';
            }
            echo '</span>';
            echo '</div>';
        }
        
        // Submission Status
        if ($isSubmitted) {
            echo '<div style="background: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 15px; border-left: 4px solid #28a745;">';
            echo '<strong style="color: #155724;"><i class="icon_check"></i> Status:</strong> Submitted on ' . date('M d, Y H:i', strtotime($row['submittedAt']));
            if ($isLate) {
                echo ' <span class="label label-danger" style="margin-left: 5px;">Late</span>';
            }
            if ($row['submissionStatus'] === 'graded' && $row['grade'] !== null) {
                echo '<br><strong style="color: #155724;">Grade:</strong> <span style="font-weight: bold; color: #28a745;">' . $row['grade'];
                if ($row['maxMarks']) {
                    echo ' / ' . $row['maxMarks'];
                }
                echo '</span>';
            }
            echo '</div>';
        }
        
        // Action Buttons
        echo '<div style="display: flex; justify-content: space-between; align-items: center;">';
        echo '<span class="label label-warning" style="font-size: 12px;"><i class="icon_document_alt"></i> Assignment</span>';
        
        echo '<div>';
        if (!$isSubmitted) {
            if ($isOverdue) {
                echo '<button class="btn btn-danger btn-sm" onclick="submitAssignment(' . $row['fileId'] . ')" title="Submit (Late)" style="margin-right: 5px;">';
                echo '<i class="icon_upload"></i> Submit (Late)';
                echo '</button>';
            } else {
                echo '<button class="btn btn-primary btn-sm" onclick="submitAssignment(' . $row['fileId'] . ')" style="margin-right: 5px;">';
                echo '<i class="icon_upload"></i> Submit';
                echo '</button>';
            }
        } else {
            echo '<button class="btn btn-success btn-sm" disabled style="margin-right: 5px;">';
            echo '<i class="icon_check"></i> Submitted';
            echo '</button>';
        }
        
        echo '<button class="btn btn-default btn-sm" onclick="downloadFile(' . $row['fileId'] . ', \'' . htmlspecialchars($row['originalName']) . '\')">';
        echo '<i class="icon_download"></i>';
        echo '</button>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>'; // panel-body
        echo '</div>'; // panel
        echo '</div>'; // col
    }
    
    echo '</div>';
} else {
    echo '<div class="alert alert-info" style="text-align: center; padding: 30px; margin: 20px 0;">';
    echo '<i class="icon_info" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>';
    echo '<h4>No assignments available</h4>';
    echo '<p>You don\'t have any assignments at the moment. Check back later!</p>';
    echo '</div>';
}
?>
