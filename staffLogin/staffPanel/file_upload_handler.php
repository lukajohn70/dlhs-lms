<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/file_assignment_helper.php";

// Ensure all database tables and columns exist
dlhsEnsureAllFileManagementTablesExist($connection);

// Check if user is logged in
if (!isset($_SESSION['staffLoggedIn']) || $_SESSION['staffLoggedIn'] !== "yes") {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

$staffId = (int)$_SESSION['staffId'];

// Configuration
$maxFileSize = 100 * 1024 * 1024; // 100MB
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
    'zip' => 'application/zip',
    'rar' => 'application/x-rar-compressed',
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
    $categoryId = isset($_POST['categoryId']) ? intval($_POST['categoryId']) : 5; // Default 5: Resources
    $title = mysqli_real_escape_string($connection, trim($_POST['title'] ?? ''));
    $description = mysqli_real_escape_string($connection, trim($_POST['description'] ?? ''));
    $uploadedFor = mysqli_real_escape_string($connection, trim($_POST['uploadedFor'] ?? 'all'));
    
    // Determine Target Scope
    if ($uploadedFor === 'admin') {
        $targetScope = 'admin';
        $adminStatus = 'pending';
        $isPublishedToStudents = 0;
    } elseif (in_array($uploadedFor, ['all_teachers', 'subject_teachers', 'specific_teachers'])) {
        $targetScope = 'teachers';
        $adminStatus = 'not_applicable';
        $isPublishedToStudents = 0;
    } else {
        $targetScope = 'students';
        $adminStatus = 'not_applicable';
        $isPublishedToStudents = 1;
    }

    $targetClassId = !empty($_POST['targetClass']) ? intval($_POST['targetClass']) : null;
    $targetClasses = isset($_POST['targetClasses']) && is_array($_POST['targetClasses']) ? array_filter(array_map('intval', $_POST['targetClasses'])) : [];
    $selectedStudents = isset($_POST['selectedStudents']) && is_array($_POST['selectedStudents']) ? array_filter(array_map('intval', $_POST['selectedStudents'])) : [];
    $selectedTeachers = isset($_POST['selectedTeachers']) && is_array($_POST['selectedTeachers']) ? array_filter(array_map('intval', $_POST['selectedTeachers'])) : [];
    
    $uploadedFiles = [];
    $errors = [];

    // Helper function to insert assignments and log access
    $processPostInsert = function($fileId) use ($connection, $staffId, $uploadedFor, $selectedStudents, $selectedTeachers) {
        if ($uploadedFor === 'specific_students' && !empty($selectedStudents)) {
            foreach ($selectedStudents as $studentId) {
                $studentId = intval($studentId);
                $assignmentQuery = "INSERT IGNORE INTO file_student_assignments (fileId, studentId) VALUES ($fileId, $studentId)";
                $connection->query($assignmentQuery);
            }
        } elseif ($uploadedFor === 'specific_teachers' && !empty($selectedTeachers)) {
            foreach ($selectedTeachers as $tId) {
                $tId = intval($tId);
                $assignmentQuery = "INSERT IGNORE INTO file_staff_assignments (fileId, staffId) VALUES ($fileId, $tId)";
                $connection->query($assignmentQuery);
            }
        }

        $logQuery = "INSERT INTO file_access_logs (fileId, userId, userType, action, ipAddress, userAgent) 
                    VALUES ($fileId, $staffId, 'staff', 'upload', '" . $_SERVER['REMOTE_ADDR'] . "', '" . 
                    mysqli_real_escape_string($connection, $_SERVER['HTTP_USER_AGENT'] ?? '') . "')";
        $connection->query($logQuery);
    };

    if ($uploadType === 'link' && !empty($_POST['youtubeLink'])) {
        $youtubeLink = mysqli_real_escape_string($connection, trim($_POST['youtubeLink']));
        
        if ($targetScope === 'students' && $uploadedFor === 'multiple_classes' && !empty($targetClasses)) {
            $classesToTarget = $targetClasses;
            $actualAudience = 'specific_class';
        } else {
            $classesToTarget = [ $targetClassId ];
            $actualAudience = $uploadedFor;
        }

        foreach ($classesToTarget as $clsId) {
            $cVal = ($clsId && $targetScope === 'students') ? (int)$clsId : 'NULL';
            $query = "INSERT INTO file_uploads (
                fileName, originalName, filePath, fileSize, fileType, categoryId, subjectId, 
                uploadedBy, uploaderType, uploadedFor, targetScope, targetClassId, title, description, 
                isAssignment, isPublishedToStudents, adminStatus
            ) VALUES (
                'youtube_link', '$youtubeLink', '$youtubeLink', 0, 'youtube', $categoryId, $subjectId,
                $staffId, 'staff', '$actualAudience', '$targetScope', $cVal, 
                '$title', '$description', 0, $isPublishedToStudents, '$adminStatus'
            )";
            
            if ($connection->query($query)) {
                $fileId = $connection->insert_id;
                $uploadedFiles[] = [
                    'id'   => $fileId,
                    'name' => 'YouTube Link',
                    'path' => $youtubeLink
                ];
                $processPostInsert($fileId);
            } else {
                $errors[] = "Database error for YouTube Link: " . $connection->error;
            }
        }

    } elseif ($uploadType === 'file' && isset($_FILES['files'])) {
        $files = $_FILES['files'];
        // Use absolute path from dirname
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
                'message' => 'Upload directory does not exist and could not be created: ' . $uploadDir
            ]);
            exit();
        }
        
        if (!is_writable($uploadDir)) {
            @chmod($uploadDir, 0777);
        }
        
        // Process each file
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $fileName = $files['name'][$i];
                $fileSize = $files['size'][$i];
                $fileTmpName = $files['tmp_name'][$i];
                $fileType = $files['type'][$i];
                
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                if (!array_key_exists($fileExt, $allowedTypes)) {
                    $errors[] = "File '$fileName' has an unsupported extension ($fileExt).";
                    continue;
                }
                
                // Sanitise filename
                $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
                $newFileName = time() . '_' . $i . '_' . $safeName;
                $filePath = $uploadDir . $newFileName;
                $filePathForDB = 'uploads/resources/' . $newFileName;
                
                if (move_uploaded_file($fileTmpName, $filePath)) {
                    $escapedFileName     = mysqli_real_escape_string($connection, $fileName);
                    $escapedNewFileName  = mysqli_real_escape_string($connection, $newFileName);
                    $escapedFilePathDB   = mysqli_real_escape_string($connection, $filePathForDB);
                    $escapedFileType     = mysqli_real_escape_string($connection, $fileType);

                    if ($targetScope === 'students' && $uploadedFor === 'multiple_classes' && !empty($targetClasses)) {
                        $classesToTarget = $targetClasses;
                        $actualAudience = 'specific_class';
                    } else {
                        $classesToTarget = [ $targetClassId ];
                        $actualAudience = $uploadedFor;
                    }

                    foreach ($classesToTarget as $clsId) {
                        $cVal = ($clsId && $targetScope === 'students') ? (int)$clsId : 'NULL';
                        $query = "INSERT INTO file_uploads (
                            fileName, originalName, filePath, fileSize, fileType, categoryId, subjectId, 
                            uploadedBy, uploaderType, uploadedFor, targetScope, targetClassId, title, description, 
                            isAssignment, isPublishedToStudents, adminStatus
                        ) VALUES (
                            '$escapedNewFileName', '$escapedFileName', '$escapedFilePathDB', $fileSize, '$escapedFileType', $categoryId, $subjectId,
                            $staffId, 'staff', '$actualAudience', '$targetScope', $cVal, 
                            '$title', '$description', 0, $isPublishedToStudents, '$adminStatus'
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
                        }
                    }
                } else {
                    $errors[] = "Failed to move uploaded file '$fileName'. Check permissions on upload folder.";
                }
            } else {
                $errors[] = "Upload error code {$files['error'][$i]} for file: " . $files['name'][$i];
            }
        }
    }
    
    // Return response
    if (count($uploadedFiles) > 0) {
        $msg = count($uploadedFiles) . " file(s) shared successfully!";
        if ($targetScope === 'admin') {
            $msg = "File submitted to School Administration successfully!";
        } elseif ($uploadedFor === 'all_teachers') {
            $msg = "File broadcast to all teachers successfully!";
        } elseif ($uploadedFor === 'specific_teachers') {
            $msg = "File shared with selected teachers successfully!";
        } elseif ($uploadedFor === 'subject_teachers') {
            $msg = "File shared with subject department colleagues!";
        }
        
        echo json_encode([
            'success' => true,
            'message' => $msg,
            'files'   => $uploadedFiles,
            'errors'  => $errors
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => !empty($errors) ? implode('; ', $errors) : 'No files were uploaded.'
        ]);
    }
    exit();
}
?>
