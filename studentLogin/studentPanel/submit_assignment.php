<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";

// Check if user is logged in
if (!isset($_SESSION['studentLoggedIn']) || $_SESSION['studentLoggedIn'] !== "yes") {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

$studentId = $_SESSION['studentId'];

// Configuration
$maxFileSize = 25 * 1024 * 1024; // 25MB for submissions
$allowedTypes = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['submissionFile'])) {
    $assignmentId = intval($_POST['assignmentId']);
    $submissionText = mysqli_real_escape_string($connection, $_POST['submissionText']);
    $file = $_FILES['submissionFile'];
    
    // Check if assignment exists and is still active
    $assignmentQuery = "SELECT * FROM file_uploads WHERE fileId = $assignmentId AND isAssignment = 1 AND isActive = 1";
    $assignmentResult = $connection->query($assignmentQuery);
    
    if (!$assignmentResult || $assignmentResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Assignment not found or no longer active']);
        exit();
    }
    
    $assignment = $assignmentResult->fetch_assoc();
    
    // Check if student has already submitted
    $existingQuery = "SELECT submissionId FROM assignment_submissions WHERE fileId = $assignmentId AND studentId = $studentId";
    $existingResult = $connection->query($existingQuery);
    
    if ($existingResult && $existingResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'You have already submitted this assignment']);
        exit();
    }
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'File upload error']);
        exit();
    }
    
    // if ($file['size'] > $maxFileSize) {
    //     echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 25MB']);
    //     exit();
    // }
    
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!array_key_exists($fileExt, $allowedTypes) || $file['type'] !== $allowedTypes[$fileExt]) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: PDF, DOC, DOCX, Images']);
        exit();
    }
    
    // Create submission directory
    $submissionDir = "../../uploads/submissions/";
    if (!file_exists($submissionDir)) {
        mkdir($submissionDir, 0755, true);
    }
    
    // Generate unique filename
    $newFileName = time() . '_' . $studentId . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
    $filePath = $submissionDir . $newFileName;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        // Check if submission is late
        $isLate = $assignment['dueDate'] && strtotime($assignment['dueDate']) < time();
        
        // Insert submission record
        $query = "INSERT INTO assignment_submissions (
            fileId, studentId, submissionFile, submissionText, isLate, status
        ) VALUES (
            $assignmentId, $studentId, '$filePath', '$submissionText', " . ($isLate ? 1 : 0) . ", 'submitted'
        )";
        
        if ($connection->query($query)) {
            $submissionId = $connection->insert_id;
            
            // Log submission
            $logQuery = "INSERT INTO file_access_logs (fileId, userId, userType, action, ipAddress, userAgent) 
                        VALUES ($assignmentId, $studentId, 'student', 'upload', '" . $_SERVER['REMOTE_ADDR'] . "', '" . 
                        mysqli_real_escape_string($connection, $_SERVER['HTTP_USER_AGENT']) . "')";
            $connection->query($logQuery);
            
            // Create notification for teacher
            $teacherQuery = "SELECT uploadedBy FROM file_uploads WHERE fileId = $assignmentId";
            $teacherResult = $connection->query($teacherQuery);
            if ($teacherResult && $teacherResult->num_rows > 0) {
                $teacher = $teacherResult->fetch_assoc();
                $studentName = $_SESSION['studentName'];
                $assignmentTitle = $assignment['title'];
                
                $notificationQuery = "INSERT INTO notifications (userId, userType, title, message, type, relatedId) 
                                    VALUES (" . $teacher['uploadedBy'] . ", 'staff', 'New Assignment Submission', 
                                    'Student $studentName submitted assignment: $assignmentTitle', 'assignment', $submissionId)";
                $connection->query($notificationQuery);
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Assignment submitted successfully' . ($isLate ? ' (Late submission)' : ''),
                'submissionId' => $submissionId
            ]);
        } else {
            // Remove uploaded file if database insert failed
            unlink($filePath);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $connection->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
