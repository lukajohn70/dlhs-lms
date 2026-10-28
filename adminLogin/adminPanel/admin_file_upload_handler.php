<?php
ob_start();
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/file_assignment_helper.php";

header('Content-Type: application/json; charset=utf-8');

dlhsEnsureAllFileManagementTablesExist($connection);

if (!isset($_SESSION['adminLoggedIn']) || $_SESSION['adminLoggedIn'] !== "yes") {
    while (ob_get_level()) { ob_end_clean(); }
    http_response_code(401);
    echo json_encode(['success' => false, 'sessionExpired' => true, 'message' => 'Admin session expired or not authorized.']);
    exit();
}

$adminId = isset($_SESSION['adminId']) && is_numeric($_SESSION['adminId']) ? (int)$_SESSION['adminId'] : 1;

// Detect if post_max_size was exceeded
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    while (ob_get_level()) { ob_end_clean(); }
    http_response_code(413);
    $serverLimit = ini_get('post_max_size') ?: 'Unknown';
    echo json_encode([
        'success' => false,
        'message' => "The uploaded file exceeds the server post_max_size limit ({$serverLimit}). Please upload a smaller file or increase limits in php.ini."
    ]);
    exit();
}

$allowedTypes = [
    // Documents & Office
    'pdf'   => 'application/pdf',
    'doc'   => 'application/msword',
    'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'ppt'   => 'application/vnd.ms-powerpoint',
    'pptx'  => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'xls'   => 'application/vnd.ms-excel',
    'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'csv'   => 'text/csv',
    'txt'   => 'text/plain',
    'rtf'   => 'application/rtf',
    'odt'   => 'application/vnd.oasis.opendocument.text',
    'ods'   => 'application/vnd.oasis.opendocument.spreadsheet',
    'odp'   => 'application/vnd.oasis.opendocument.presentation',
    // Compressed Archives
    'zip'   => 'application/zip',
    'rar'   => 'application/x-rar-compressed',
    '7z'    => 'application/x-7z-compressed',
    'tar'   => 'application/x-tar',
    'gz'    => 'application/gzip',
    // Images
    'jpg'   => 'image/jpeg',
    'jpeg'  => 'image/jpeg',
    'png'   => 'image/png',
    'gif'   => 'image/gif',
    'webp'  => 'image/webp',
    'svg'   => 'image/svg+xml',
    // Media - Video
    'mp4'   => 'video/mp4',
    'webm'  => 'video/webm',
    'avi'   => 'video/x-msvideo',
    'mov'   => 'video/quicktime',
    'mkv'   => 'video/x-matroska',
    // Media - Audio
    'mp3'   => 'audio/mpeg',
    'wav'   => 'audio/wav',
    'm4a'   => 'audio/mp4',
    'aac'   => 'audio/aac',
    'ogg'   => 'audio/ogg'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $uploadType  = isset($_POST['uploadType']) ? $_POST['uploadType'] : 'file';
        $subjectId   = isset($_POST['subject']) ? intval($_POST['subject']) : 0;
        $categoryId  = isset($_POST['categoryId']) ? intval($_POST['categoryId']) : 9; // Default 9: Administrative
        $title       = mysqli_real_escape_string($connection, trim($_POST['title'] ?? ''));
        $description = mysqli_real_escape_string($connection, trim($_POST['description'] ?? ''));
        $uploadedFor = mysqli_real_escape_string($connection, trim($_POST['uploadedFor'] ?? 'all_teachers'));

        if (in_array($uploadedFor, ['all_teachers', 'specific_teachers'])) {
            $targetScope = 'teachers';
            $isPublishedToStudents = 0;
        } else {
            $targetScope = 'students';
            $isPublishedToStudents = 1;
        }

        $targetClassId    = !empty($_POST['targetClass']) ? intval($_POST['targetClass']) : null;
        $selectedTeachers = isset($_POST['selectedTeachers']) && is_array($_POST['selectedTeachers']) ? array_filter(array_map('intval', $_POST['selectedTeachers'])) : [];

        $uploadedFiles = [];
        $errors = [];

        $processPostInsert = function($fileId) use ($connection, $adminId, $uploadedFor, $selectedTeachers) {
            if ($uploadedFor === 'specific_teachers' && !empty($selectedTeachers)) {
                foreach ($selectedTeachers as $tId) {
                    $tId = intval($tId);
                    @$connection->query("INSERT IGNORE INTO file_staff_assignments (fileId, staffId) VALUES ($fileId, $tId)");
                }
            }

            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = mysqli_real_escape_string($connection, $_SERVER['HTTP_USER_AGENT'] ?? '');
            @$connection->query("INSERT INTO file_access_logs (fileId, userId, userType, action, ipAddress, userAgent) 
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
                $fileName    = $files['name'][$i];
                $fileError   = $files['error'][$i];
                $fileSize    = $files['size'][$i];
                $fileTmpName = $files['tmp_name'][$i];
                $fileType    = $files['type'][$i];

                if ($fileError !== UPLOAD_ERR_OK) {
                    switch ($fileError) {
                        case UPLOAD_ERR_INI_SIZE:
                            $iniSize = ini_get('upload_max_filesize') ?: '2M';
                            $errors[] = "File '$fileName' exceeds server upload limit ({$iniSize}).";
                            break;
                        case UPLOAD_ERR_FORM_SIZE:
                            $errors[] = "File '$fileName' exceeds form size limit.";
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            $errors[] = "File '$fileName' was only partially uploaded.";
                            break;
                        case UPLOAD_ERR_NO_FILE:
                            $errors[] = "No file was attached.";
                            break;
                        case UPLOAD_ERR_CANT_WRITE:
                            $errors[] = "Server failed to write '$fileName' to disk.";
                            break;
                        default:
                            $errors[] = "Upload failed for '$fileName' with error code $fileError.";
                    }
                    continue;
                }

                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                if (!array_key_exists($fileExt, $allowedTypes)) {
                    $errors[] = "File '$fileName' has an unsupported file format (.$fileExt).";
                    continue;
                }

                $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
                $newFileName = 'admin_' . time() . '_' . $i . '_' . $safeName;
                $destPath = $uploadDir . $newFileName;
                $dbPath = 'uploads/resources/' . $newFileName;

                if (move_uploaded_file($fileTmpName, $destPath)) {
                    $escapedFileName    = mysqli_real_escape_string($connection, $fileName);
                    $escapedNewFileName = mysqli_real_escape_string($connection, $newFileName);
                    $escapedDbPath      = mysqli_real_escape_string($connection, $dbPath);
                    $escapedFileType    = mysqli_real_escape_string($connection, $fileType);
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
            }
        }

        while (ob_get_level()) { ob_end_clean(); }

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

    } catch (\Throwable $e) {
        while (ob_get_level()) { ob_end_clean(); }
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Server error while processing upload: ' . $e->getMessage()
        ]);
        exit();
    }
}
?>