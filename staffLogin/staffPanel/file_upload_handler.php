<?php
ob_start();
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/file_assignment_helper.php";

header('Content-Type: application/json; charset=utf-8');

// Ensure all database tables and columns exist
dlhsEnsureAllFileManagementTablesExist($connection);

// Check if user is logged in
if (!isset($_SESSION['staffLoggedIn']) || $_SESSION['staffLoggedIn'] !== "yes") {
    while (ob_get_level()) { ob_end_clean(); }
    http_response_code(401);
    echo json_encode(['success' => false, 'sessionExpired' => true, 'message' => 'Your session has expired or you are not authorized. Please log in again.']);
    exit();
}

$staffId = (int)$_SESSION['staffId'];

// Configuration
$maxFileSize = 100 * 1024 * 1024; // 100MB
$allowedTypes = [
    // Documents
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'ppt'  => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'xls'  => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'txt'  => 'text/plain',
    'zip'  => 'application/zip',
    'rar'  => 'application/x-rar-compressed',
    // Images
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    // Media
    'mp4'  => 'video/mp4',
    'avi'  => 'video/x-msvideo',
    'mov'  => 'video/quicktime',
    'mkv'  => 'video/x-matroska',
    'mp3'  => 'audio/mpeg',
    'wav'  => 'audio/wav'
];

// Detect if post_max_size was exceeded (PHP empties $_POST and $_FILES in this scenario)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    while (ob_get_level()) { ob_end_clean(); }
    http_response_code(413);
    $serverLimit = ini_get('post_max_size') ?: 'Unknown';
    echo json_encode([
        'success' => false,
        'message' => "The uploaded file exceeds the server's post_max_size limit ({$serverLimit}). Please upload a smaller file or increase post_max_size and upload_max_filesize in php.ini."
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $uploadType  = isset($_POST['uploadType']) ? $_POST['uploadType'] : 'file';
        $subjectId   = isset($_POST['subject']) ? intval($_POST['subject']) : 0;
        $categoryId  = isset($_POST['categoryId']) ? intval($_POST['categoryId']) : 5; // Default 5: Resources
        $title       = mysqli_real_escape_string($connection, trim($_POST['title'] ?? ''));
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

        $targetClassId    = !empty($_POST['targetClass']) ? intval($_POST['targetClass']) : null;
        $targetClasses    = isset($_POST['targetClasses']) && is_array($_POST['targetClasses']) ? array_filter(array_map('intval', $_POST['targetClasses'])) : [];
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
                    @$connection->query($assignmentQuery);
                }
            } elseif ($uploadedFor === 'specific_teachers' && !empty($selectedTeachers)) {
                foreach ($selectedTeachers as $tId) {
                    $tId = intval($tId);
                    $assignmentQuery = "INSERT IGNORE INTO file_staff_assignments (fileId, staffId) VALUES ($fileId, $tId)";
                    @$connection->query($assignmentQuery);
                }
            }

            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = mysqli_real_escape_string($connection, $_SERVER['HTTP_USER_AGENT'] ?? '');
            $logQuery = "INSERT INTO file_access_logs (fileId, userId, userType, action, ipAddress, userAgent) 
                         VALUES ($fileId, $staffId, 'staff', 'upload', '$ip', '$ua')";
            @$connection->query($logQuery);
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
            $uploadDir = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR;

            $dirExists = is_dir($uploadDir);
            if (!$dirExists) {
                $dirExists = @mkdir($uploadDir, 0777, true);
                if ($dirExists) {
                    @chmod($uploadDir, 0777);
                }
            }

            if (!$dirExists) {
                while (ob_get_level()) { ob_end_clean(); }
                echo json_encode([
                    'success' => false, 
                    'message' => 'Upload directory does not exist and could not be created on server: ' . $uploadDir
                ]);
                exit();
            }

            if (!is_writable($uploadDir)) {
                @chmod($uploadDir, 0777);
            }

            // Process each file
            for ($i = 0; $i < count($files['name']); $i++) {
                $fileName    = $files['name'][$i];
                $fileError   = $files['error'][$i];
                $fileSize    = $files['size'][$i];
                $fileTmpName = $files['tmp_name'][$i];
                $fileType    = $files['type'][$i];

                if ($fileError !== UPLOAD_ERR_OK) {
                    switch ($fileError) {
                        case UPLOAD_ERR_INI_SIZE:
                            $iniSize = ini_get('upload_max_filesize') ?: '2M';
                            $errors[] = "File '$fileName' exceeds server upload_max_filesize limit ({$iniSize}). Please upload a smaller file or increase upload_max_filesize in php.ini.";
                            break;
                        case UPLOAD_ERR_FORM_SIZE:
                            $errors[] = "File '$fileName' exceeds the MAX_FILE_SIZE specified in the form.";
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            $errors[] = "File '$fileName' was only partially uploaded. Please check your internet connection and try again.";
                            break;
                        case UPLOAD_ERR_NO_FILE:
                            $errors[] = "No file was uploaded.";
                            break;
                        case UPLOAD_ERR_NO_TMP_DIR:
                            $errors[] = "Server error: Missing temporary upload folder.";
                            break;
                        case UPLOAD_ERR_CANT_WRITE:
                            $errors[] = "Server error: Failed to write file '$fileName' to disk. Check permissions and disk space.";
                            break;
                        default:
                            $errors[] = "Upload failed for '$fileName' with error code $fileError.";
                    }
                    continue;
                }

                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                if (!array_key_exists($fileExt, $allowedTypes)) {
                    $errors[] = "File '$fileName' has an unsupported extension (.$fileExt). Allowed formats: PDF, Word, Excel, PowerPoint, Text, ZIP, Images, MP3, MP4.";
                    continue;
                }

                if ($fileSize > $maxFileSize) {
                    $errors[] = "File '$fileName' exceeds the maximum allowed size (100MB).";
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
                    $errors[] = "Failed to save uploaded file '$fileName' on server. Check folder write permissions on 'uploads/resources/'.";
                }
            }
        }

        while (ob_get_level()) { ob_end_clean(); }

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
                'message' => !empty($errors) ? implode('; ', $errors) : 'No files were uploaded. Please check file format and size.'
            ]);
        }
        exit();

    } catch (\Throwable $e) {
        while (ob_get_level()) { ob_end_clean(); }
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Server error while processing upload: ' . $e->getMessage()
        ]);
        exit();
    }
} else {
    while (ob_get_level()) { ob_end_clean(); }
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}
