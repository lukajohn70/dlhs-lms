<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/file_assignment_helper.php";

// Ensure all database tables exist
dlhsEnsureAllFileManagementTablesExist($connection);

// Check if user is logged in
if (!isset($_SESSION['staffLoggedIn']) || $_SESSION['staffLoggedIn'] !== "yes") {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

$staffId = $_SESSION['staffId'];

// Configuration
$maxFileSize = 100 * 1024 * 1024; // 100MB (increased for videos/slides)
$allowedTypes = [
    // Documents
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'ppt' => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'txt' => 'text/plain',
    // Images
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    // Media
    'mp4' => 'video/mp4',
    'avi' => 'video/x-msvideo',
    'mov' => 'video/quicktime',
    'mkv' => 'video/x-matroska',
    'mp3' => 'audio/mpeg',
    'wav' => 'audio/wav'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadType = isset($_POST['uploadType']) ? $_POST['uploadType'] : 'file';
    $subjectId = isset($_POST['subject']) ? intval($_POST['subject']) : 0;
    $title = mysqli_real_escape_string($connection, $_POST['title']);
    $description = mysqli_real_escape_string($connection, $_POST['description']);
    $uploadedFor = mysqli_real_escape_string($connection, $_POST['uploadedFor']);
    $targetClassId = !empty($_POST['targetClass']) ? intval($_POST['targetClass']) : null;
    $selectedStudents = isset($_POST['selectedStudents']) ? $_POST['selectedStudents'] : [];
    
    $uploadedFiles = [];
    $errors = [];

    // Helper function to insert assignments and log access
    $processPostInsert = function($fileId) use ($connection, $staffId, $uploadedFor, $selectedStudents) {
        if ($uploadedFor === 'specific_students' && !empty($selectedStudents)) {
            foreach ($selectedStudents as $studentId) {
                $studentId = intval($studentId);
                $assignmentQuery = "INSERT INTO file_student_assignments (fileId, studentId) VALUES ($fileId, $studentId)";
                $connection->query($assignmentQuery);
            }
        }
        $logQuery = "INSERT INTO file_access_logs (fileId, userId, userType, action, ipAddress, userAgent) 
                    VALUES ($fileId, $staffId, 'staff', 'upload', '" . $_SERVER['REMOTE_ADDR'] . "', '" . 
                    mysqli_real_escape_string($connection, $_SERVER['HTTP_USER_AGENT']) . "')";
        $connection->query($logQuery);
    };

    if ($uploadType === 'link' && !empty($_POST['youtubeLink'])) {
        $youtubeLink = mysqli_real_escape_string($connection, trim($_POST['youtubeLink']));
        
        $query = "INSERT INTO file_uploads (
            fileName, originalName, filePath, fileSize, fileType, categoryId, subjectId, 
            uploadedBy, uploadedFor, targetClassId, title, description, isAssignment, isPublishedToStudents
        ) VALUES (
            'youtube_link', '$youtubeLink', '$youtubeLink', 0, 'youtube', 5, $subjectId,
            $staffId, '$uploadedFor', " . ($targetClassId ? $targetClassId : 'NULL') . ", 
            '$title', '$description', 0, 1
        )";
        
        if ($connection->query($query)) {
            $fileId = $connection->insert_id;
            $uploadedFiles[] = [
                'id' => $fileId,
                'name' => 'YouTube Link',
                'path' => $youtubeLink
            ];
            $processPostInsert($fileId);
        } else {
            $errors[] = "Database error for YouTube Link: " . $connection->error;
        }

    } elseif ($uploadType === 'file' && isset($_FILES['files'])) {
        $files = $_FILES['files'];
        // Use absolute path from dirname with robust checks and diagnostics
        $uploadDir = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR;
        
        $dirExists = is_dir($uploadDir);
        if (!$dirExists) {
            $dirExists = @mkdir($uploadDir, 0777, true);
            if ($dirExists) {
                @chmod($uploadDir, 0777);
            }
        }
        
        if (!$dirExists) {
            echo json_encode([
                'success' => false, 
                'message' => 'Upload directory does not exist and could not be created: ' . $uploadDir . '. Please check parent folder permissions.'
            ]);
            exit();
        }
        
        if (!is_writable($uploadDir)) {
            @chmod($uploadDir, 0777);
            if (!is_writable($uploadDir)) {
                $currentUser = function_exists('posix_getpwuid') && function_exists('posix_geteuid') 
                    ? posix_getpwuid(posix_geteuid())['name'] 
                    : get_current_user();
                echo json_encode([
                    'success' => false, 
                    'message' => 'Upload directory is not writable: ' . $uploadDir . '. Executing process user: ' . $currentUser . '. Please check directory permissions.'
                ]);
                exit();
            }
        }
        
        // Process each file
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $fileName = $files['name'][$i];
                $fileSize = $files['size'][$i];
                $fileTmpName = $files['tmp_name'][$i];
                $fileType = $files['type'][$i];
                
                // if ($fileSize > $maxFileSize) {
                //     $errors[] = "File '$fileName' is too large. Maximum size is 100MB.";
                //     continue;
                // }
                
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                if (!array_key_exists($fileExt, $allowedTypes)) {
                    $errors[] = "File '$fileName' has an invalid extension ($fileExt).";
                    continue;
                }
                
                // Sanitise filename — strip special chars but keep extension
                $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
                $newFileName = time() . '_' . $i . '_' . $safeName;
                $filePath = $uploadDir . $newFileName;
                $filePathForDB = 'uploads/resources/' . $newFileName;
                
                if (move_uploaded_file($fileTmpName, $filePath)) {
                    $escapedFileName     = mysqli_real_escape_string($connection, $fileName);
                    $escapedNewFileName  = mysqli_real_escape_string($connection, $newFileName);
                    $escapedFilePathDB   = mysqli_real_escape_string($connection, $filePathForDB);
                    $escapedFileType     = mysqli_real_escape_string($connection, $fileType);
                    $query = "INSERT INTO file_uploads (
                        fileName, originalName, filePath, fileSize, fileType, categoryId, subjectId, 
                        uploadedBy, uploadedFor, targetClassId, title, description, isAssignment, isPublishedToStudents
                    ) VALUES (
                        '$escapedNewFileName', '$escapedFileName', '$escapedFilePathDB', $fileSize, '$escapedFileType', 5, $subjectId,
                        $staffId, '$uploadedFor', " . ($targetClassId ? $targetClassId : 'NULL') . ", 
                        '$title', '$description', 0, 1
                    )";
                    
                    if ($connection->query($query)) {
                        $fileId = $connection->insert_id;
                        $uploadedFiles[] = [
                            'id'   => $fileId,
                            'name' => $fileName,
                            'path' => $filePathForDB
                        ];
                        $processPostInsert($fileId);
                    } else {
                        $errors[] = "Database error for file '$fileName': " . $connection->error;
                        @unlink($filePath);
                    }
                } else {
                    $lastErr = error_get_last();
                    $errors[] = "Failed to move uploaded file '$fileName' to '$filePath'. Check permissions on: $uploadDir. Error: " . ($lastErr ? $lastErr['message'] : 'Unknown') . " (is_uploaded: " . (is_uploaded_file($fileTmpName) ? 'yes' : 'no') . ")";
                }
            } else {
                $errors[] = "Upload error code {$files['error'][$i]} for file: " . $files['name'][$i];
            }
        }
    }
    
    // Return response
    if (count($uploadedFiles) > 0) {
        $response = [
            'success' => true,
            'message' => count($uploadedFiles) . ' item(s) shared successfully',
            'files' => $uploadedFiles
        ];
        
        if (count($errors) > 0) {
            $response['warnings'] = $errors;
        }
    } else {
        $errorMsg = 'No items were uploaded successfully.';
        if (!empty($errors)) {
            $errorMsg .= ' Details: ' . implode(' | ', $errors);
        }
        $response = [
            'success' => false,
            'message' => $errorMsg,
            'errors' => $errors
        ];
    }
    
    echo json_encode($response);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
