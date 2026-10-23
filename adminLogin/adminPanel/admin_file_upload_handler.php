<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/file_assignment_helper.php";

dlhsEnsureAllFileManagementTablesExist($connection);

if (!isset($_SESSION['adminLoggedIn']) || $_SESSION['adminLoggedIn'] !== "yes") {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

$adminId = isset($_SESSION['adminId']) && is_numeric($_SESSION['adminId']) ? (int)$_SESSION['adminId'] : 1;

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
    $categoryId = isset($_POST['categoryId']) ? intval($_POST['categoryId']) : 9; // Default 9: Administrative
    $title = mysqli_real_escape_string($connection, trim($_POST['title'] ?? ''));
    $description = mysqli_real_escape_string($connection, trim($_POST['description'] ?? ''));
    $uploadedFor = mysqli_real_escape_string($connection, trim($_POST['uploadedFor'] ?? 'all_teachers'));

    if (in_array($uploadedFor, ['all_teachers', 'specific_teachers'])) {
        $targetScope = 'teachers';
        $isPublishedToStudents = 0;
    } else {
        $targetScope = 'students';
        $isPublishedToStudents = 1;
    }

    $targetClassId = !empty($_POST['targetClass']) ? intval($_POST['targetClass']) : null;
    $selectedTeachers = isset($_POST['selectedTeachers']) && is_array($_POST['selectedTeachers']) ? array_filter(array_map('intval', $_POST['selectedTeachers'])) : [];

    $uploadedFiles = [];
    $errors = [];

    $processPostInsert = function($fileId) use ($connection, $adminId, $uploadedFor, $selectedTeachers) {
        if ($uploadedFor === 'specific_teachers' && !empty($selectedTeachers)) {
            foreach ($selectedTeachers as $tId) {
                $tId = intval($tId);
                $connection->query("INSERT IGNORE INTO file_staff_assignments (fileId, staffId) VALUES ($fileId, $tId)");
            }
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = mysqli_real_escape_string($connection, $_SERVER['HTTP_USER_AGENT'] ?? '');
        $connection->query("INSERT INTO file_access_logs (fileId, userId, userType, action, ipAddress, userAgent) 
                            VALUES ($fileId, $adminId, 'admin', 'upload', '$ip', '$ua')");
    };

    if ($uploadType === 'link' && !empty($_POST['youtubeLink'])) {
        $youtubeLink = mysqli_real_escape_string($connection, trim($_POST['youtubeLink']));
        $cVal = ($targetClassId && $targetScope === 'students') ? (int)$targetClassId : 'NULL';

        $query = "INSERT INTO file_uploads (
            fileName, originalName, filePath, fileSize, fileType, categoryId, subjectId,
            uploadedBy, uploaderType, uploadedFor, targetScope, targetClassId, title, description,
            isAssignment, isPublishedToStudents, adminStatus
        ) VALUES (
            'youtube_link', '$youtubeLink', '$youtubeLink', 0, 'youtube', $categoryId, $subjectId,
            $adminId, 'admin', '$uploadedFor', '$targetScope', $cVal,
            '$title', '$description', 0, $isPublishedToStudents, 'not_applicable'
        )";

        if ($connection->query($query)) {
            $fileId = $connection->insert_id;
            $uploadedFiles[] = ['id' => $fileId, 'name' => 'YouTube Link', 'path' => $youtubeLink];
            $processPostInsert($fileId);
        } else {
            $errors[] = "Database error for YouTube link: " . $connection->error;
        }

    } elseif ($uploadType === 'file' && isset($_FILES['files'])) {
        $files = $_FILES['files'];
        $uploadDir = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR;

        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }
        @chmod($uploadDir, 0777);

        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $fileName = $files['name'][$i];
                $fileSize = $files['size'][$i];
                $fileTmpName = $files['tmp_name'][$i];
                $fileType = $files['type'][$i];

                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                if (!array_key_exists($fileExt, $allowedTypes)) {
                    $errors[] = "File '$fileName' has an unsupported file format ($fileExt).";
                    continue;
                }

                $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
                $newFileName = 'admin_' . time() . '_' . $i . '_' . $safeName;
                $destPath = $uploadDir . $newFileName;
                $dbPath = 'uploads/resources/' . $newFileName;

                if (move_uploaded_file($fileTmpName, $destPath)) {
                    $escapedFileName = mysqli_real_escape_string($connection, $fileName);
                    $escapedNewFileName = mysqli_real_escape_string($connection, $newFileName);
                    $escapedDbPath = mysqli_real_escape_string($connection, $dbPath);
                    $escapedFileType = mysqli_real_escape_string($connection, $fileType);
                    $cVal = ($targetClassId && $targetScope === 'students') ? (int)$targetClassId : 'NULL';

                    $query = "INSERT INTO file_uploads (
                        fileName, originalName, filePath, fileSize, fileType, categoryId, subjectId,
                        uploadedBy, uploaderType, uploadedFor, targetScope, targetClassId, title, description,
                        isAssignment, isPublishedToStudents, adminStatus
                    ) VALUES (
                        '$escapedNewFileName', '$escapedFileName', '$escapedDbPath', $fileSize, '$escapedFileType', $categoryId, $subjectId,
                        $adminId, 'admin', '$uploadedFor', '$targetScope', $cVal,
                        '$title', '$description', 0, $isPublishedToStudents, 'not_applicable'
                    )";

                    if ($connection->query($query)) {
                        $fileId = $connection->insert_id;
                        $uploadedFiles[] = ['id' => $fileId, 'name' => $fileName, 'path' => $dbPath];
                        $processPostInsert($fileId);
                    } else {
                        $errors[] = "Database error for file '$fileName': " . $connection->error;
                    }
                } else {
                    $errors[] = "Failed to upload file '$fileName'. Check server directory permissions.";
                }
            } else {
                $errors[] = "Upload error for file: " . $files['name'][$i];
            }
        }
    }

    if (count($uploadedFiles) > 0) {
        $msg = "File(s) shared successfully!";
        if ($uploadedFor === 'all_teachers') {
            $msg = "Document broadcast to all teaching staff successfully!";
        } elseif ($uploadedFor === 'specific_teachers') {
            $msg = "Document sent to selected teachers successfully!";
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
            'message' => !empty($errors) ? implode('; ', $errors) : 'No files uploaded.'
        ]);
    }
    exit();
}
?>
