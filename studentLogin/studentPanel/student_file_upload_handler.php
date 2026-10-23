<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/file_assignment_helper.php";

dlhsEnsureAllFileManagementTablesExist($connection);

if (!isset($_SESSION['studentLoggedIn']) || $_SESSION['studentLoggedIn'] !== "yes") {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

$studentId = (int)$_SESSION['studentId'];

// Get student's classId
$studentQuery = "SELECT classId FROM studentlogin WHERE studentId = $studentId";
$studentResult = $connection->query($studentQuery);
$studentClassId = 0;
if ($studentResult && $studentResult->num_rows > 0) {
    $r = $studentResult->fetch_assoc();
    $studentClassId = (int)$r['classId'];
}

$allowedTypes = [
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
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'mp4' => 'video/mp4',
    'mp3' => 'audio/mpeg'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetStaffId = isset($_POST['targetStaffId']) ? intval($_POST['targetStaffId']) : 0;
    $subjectId = isset($_POST['subjectId']) ? intval($_POST['subjectId']) : 0;
    $title = mysqli_real_escape_string($connection, trim($_POST['title'] ?? ''));
    $description = mysqli_real_escape_string($connection, trim($_POST['description'] ?? ''));

    if ($targetStaffId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Please select a teacher to submit this file to.']);
        exit();
    }

    // Verify recipient is a valid staff member
    $staffCheck = $connection->query("SELECT staffId FROM stafflogin WHERE staffId = $targetStaffId LIMIT 1");
    if (!$staffCheck || $staffCheck->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid teacher selected. Students can only send files to teachers.']);
        exit();
    }

    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Please provide a title for your document.']);
        exit();
    }

    if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
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
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $fileName = $files['name'][$i];
            $fileSize = $files['size'][$i];
            $fileTmpName = $files['tmp_name'][$i];
            $fileType = $files['type'][$i];

            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!array_key_exists($fileExt, $allowedTypes)) {
                $errors[] = "File '$fileName' has an unsupported format ($fileExt).";
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
                $escapedNewName = mysqli_real_escape_string($connection, $newFileName);
                $escapedDbPath = mysqli_real_escape_string($connection, $dbPath);
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

                    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
                    $ua = mysqli_real_escape_string($connection, $_SERVER['HTTP_USER_AGENT'] ?? '');
                    $connection->query("INSERT INTO file_access_logs (fileId, userId, userType, action, ipAddress, userAgent)
                                        VALUES ($fileId, $studentId, 'student', 'submit_to_teacher', '$ip', '$ua')");
                } else {
                    $errors[] = "Database error for '$fileName': " . $connection->error;
                }
            } else {
                $errors[] = "Failed to upload '$fileName'.";
            }
        }
    }

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
            'message' => !empty($errors) ? implode('; ', $errors) : 'No files uploaded.'
        ]);
    }
    exit();
}
?>
