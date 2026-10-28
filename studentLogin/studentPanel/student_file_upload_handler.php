<?php
ob_start();
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/file_assignment_helper.php";

header('Content-Type: application/json; charset=utf-8');

dlhsEnsureAllFileManagementTablesExist($connection);

if (!isset($_SESSION['studentLoggedIn']) || $_SESSION['studentLoggedIn'] !== "yes") {
    while (ob_get_level()) { ob_end_clean(); }
    http_response_code(401);
    echo json_encode(['success' => false, 'sessionExpired' => true, 'message' => 'Your student session has expired. Please log in again.']);
    exit();
}

$studentId = (int)$_SESSION['studentId'];

// Detect if post_max_size was exceeded
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    while (ob_get_level()) { ob_end_clean(); }
    http_response_code(413);
    $serverLimit = ini_get('post_max_size') ?: 'Unknown';
    echo json_encode([
        'success' => false,
        'message' => "The uploaded file exceeds the server post limit ({$serverLimit}). Please upload a smaller file."
    ]);
    exit();
}

// Get student's classId
$studentQuery = "SELECT classId FROM studentlogin WHERE studentId = $studentId";
$studentResult = $connection->query($studentQuery);
$studentClassId = 0;
if ($studentResult && $studentResult->num_rows > 0) {
    $r = $studentResult->fetch_assoc();
    $studentClassId = (int)$r['classId'];
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
        $targetStaffId = isset($_POST['targetStaffId']) ? intval($_POST['targetStaffId']) : 0;
        $subjectId     = isset($_POST['subjectId']) ? intval($_POST['subjectId']) : 0;
        $title         = mysqli_real_escape_string($connection, trim($_POST['title'] ?? ''));
        $description   = mysqli_real_escape_string($connection, trim($_POST['description'] ?? ''));

        if ($targetStaffId <= 0) {
            while (ob_get_level()) { ob_end_clean(); }
            echo json_encode(['success' => false, 'message' => 'Please select a teacher to submit this file to.']);
            exit();
        }

        // Verify recipient is a valid staff member
        $staffCheck = $connection->query("SELECT staffId FROM stafflogin WHERE staffId = $targetStaffId LIMIT 1");
        if (!$staffCheck || $staffCheck->num_rows === 0) {
            while (ob_get_level()) { ob_end_clean(); }
            echo json_encode(['success' => false, 'message' => 'Invalid teacher selected. Students can only send files to teachers.']);
            exit();
        }

        if (empty($title)) {
            while (ob_get_level()) { ob_end_clean(); }
            echo json_encode(['success' => false, 'message' => 'Please provide a title for your document.']);
            exit();
        }

        if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
            while (ob_get_level()) { ob_end_clean(); }
            echo json_encode(['success' => false, 'message' => 'Please select a file to upload.']);
            exit();
        }

        $files = $_FILES['files'];
        $uploadDir = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR;

        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }
        @chmod($uploadDir, 0777);

        $uploadedFiles = [];
        $errors = [];

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
                $errors[] = "File '$fileName' has an unsupported format (.$fileExt).";
                continue;
            }

            if ($fileSize > 50 * 1024 * 1024) { // 50MB
                $errors[] = "File '$fileName' exceeds the 50MB limit.";
                continue;
            }

            $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
            $newFileName = 'student_' . $studentId . '_' . time() . '_' . $i . '_' . $safeName;
            $destPath = $uploadDir . $newFileName;
            $dbPath = 'uploads/resources/' . $newFileName;

            if (move_uploaded_file($fileTmpName, $destPath)) {
                $escapedOrigName = mysqli_real_escape_string($connection, $fileName);
                $escapedNewName  = mysqli_real_escape_string($connection, $newFileName);
                $escapedDbPath   = mysqli_real_escape_string($connection, $dbPath);
                $escapedFileType = mysqli_real_escape_string($connection, $fileType);

                $query = "INSERT INTO file_uploads (
                    fileName, originalName, filePath, fileSize, fileType, categoryId, subjectId,
                    uploadedBy, uploaderType, uploadedFor, targetScope, targetStaffId, targetClassId,
                    title, description, isAssignment, isPublishedToStudents, adminStatus
                ) VALUES (
                    '$escapedNewName', '$escapedOrigName', '$escapedDbPath', $fileSize, '$escapedFileType', 1, $subjectId,
                    $studentId, 'student', 'specific_staff', 'staff', $targetStaffId, $studentClassId,
                    '$title', '$description', 0, 0, 'not_applicable'
                )";

                if ($connection->query($query)) {
                    $fileId = $connection->insert_id;
                    $uploadedFiles[] = ['id' => $fileId, 'name' => $fileName];

                    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                    $ua = mysqli_real_escape_string($connection, $_SERVER['HTTP_USER_AGENT'] ?? '');
                    @$connection->query("INSERT INTO file_access_logs (fileId, userId, userType, action, ipAddress, userAgent)
                                         VALUES ($fileId, $studentId, 'student', 'submit_to_teacher', '$ip', '$ua')");
                } else {
                    $errors[] = "Database error for '$fileName': " . $connection->error;
                }
            } else {
                $errors[] = "Failed to upload '$fileName' to server storage.";
            }
        }

        while (ob_get_level()) { ob_end_clean(); }

        if (count($uploadedFiles) > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Document submitted to teacher successfully!',
                'files'   => $uploadedFiles,
                'errors'  => $errors
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => !empty($errors) ? implode('; ', $errors) : 'No files uploaded. Please check file format and size.'
            ]);
        }
        exit();

    } catch (\Throwable $e) {
        while (ob_get_level()) { ob_end_clean(); }
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Server error while processing submission: ' . $e->getMessage()
        ]);
        exit();
    }
}
?>