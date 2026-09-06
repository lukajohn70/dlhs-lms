<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";

// Check if user is logged in
if (!isset($_SESSION['staffLoggedIn']) || $_SESSION['staffLoggedIn'] !== "yes") {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

$staffId = $_SESSION['staffId'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submissionId = intval($_POST['submissionId']);
    $grade = floatval($_POST['grade']);
    $feedback = mysqli_real_escape_string($connection, $_POST['feedback']);
    
    // Get submission details
    $query = "SELECT sub.*, f.title as assignmentTitle, f.maxMarks, f.uploadedBy, s.surname, s.firstName
              FROM assignment_submissions sub
              JOIN file_uploads f ON sub.fileId = f.fileId
              JOIN studentlogin s ON sub.studentId = s.studentId
              WHERE sub.submissionId = $submissionId AND f.uploadedBy = $staffId";
    
    $result = $connection->query($query);
    
    if (!$result || $result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Submission not found or not authorized']);
        exit();
    }
    
    $submission = $result->fetch_assoc();
    
    // Validate grade
    if ($grade < 0) {
        echo json_encode(['success' => false, 'message' => 'Grade cannot be negative']);
        exit();
    }
    
    if ($submission['maxMarks'] && $grade > $submission['maxMarks']) {
        echo json_encode(['success' => false, 'message' => 'Grade cannot exceed maximum marks (' . $submission['maxMarks'] . ')']);
        exit();
    }
    
    // Update submission
    $updateQuery = "UPDATE assignment_submissions 
                    SET grade = $grade, feedback = '$feedback', status = 'graded', 
                        gradedBy = $staffId, gradedAt = NOW() 
                    WHERE submissionId = $submissionId";
    
    if ($connection->query($updateQuery)) {
        // Insert into student performance tracking
        $performanceQuery = "INSERT INTO student_performance 
                            (studentId, subjectId, assignmentId, grade, maxMarks, percentage, 
                             semester, academicYear, recordedBy) 
                            SELECT 
                                sub.studentId, 
                                f.subjectId, 
                                f.fileId,
                                $grade,
                                " . ($submission['maxMarks'] ? $submission['maxMarks'] : 'NULL') . ",
                                " . ($submission['maxMarks'] ? "($grade / " . $submission['maxMarks'] . ") * 100" : 'NULL') . ",
                                'Current',
                                YEAR(NOW()),
                                $staffId
                            FROM assignment_submissions sub
                            JOIN file_uploads f ON sub.fileId = f.fileId
                            WHERE sub.submissionId = $submissionId";
        
        $connection->query($performanceQuery);
        
        // Create notification for student
        $studentId = $submission['studentId'];
        $studentName = $submission['surname'] . ' ' . $submission['firstName'];
        $assignmentTitle = $submission['assignmentTitle'];
        
        $notificationQuery = "INSERT INTO notifications (userId, userType, title, message, type, relatedId) 
                            VALUES ($studentId, 'student', 'Assignment Graded', 
                            'Your assignment \"$assignmentTitle\" has been graded. Grade: $grade" . 
                            ($submission['maxMarks'] ? "/" . $submission['maxMarks'] : '') . "', 'grade', $submissionId)";
        $connection->query($notificationQuery);
        
        echo json_encode(['success' => true, 'message' => 'Grade submitted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $connection->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
