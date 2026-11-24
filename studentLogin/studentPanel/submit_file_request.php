<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/userExpiredSession.php";
require_once __DIR__ . "/../../db_connection/dlhs_db_connection.php";
require_once __DIR__ . "/../../scripts/file_request_helper.php";

header('Content-Type: application/json; charset=utf-8');

// Ensure database tables exist
dlhsEnsureFileRequestTablesExist($connection);

// Detect if post_max_size was exceeded (PHP empties $_POST and $_FILES in this scenario)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    while (ob_get_level()) { ob_end_clean(); }
    http_response_code(413);
    $serverLimit = ini_get('post_max_size') ?: 'Unknown';
    echo json_encode([
        'success' => false,
        'message' => "The uploaded file exceeds the server's post_max_size limit ({$serverLimit}). Please upload a smaller file or ask your administrator to increase post_max_size in php.ini."
    ]);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['studentLoggedIn']) || $_SESSION['studentLoggedIn'] !== "yes" || empty($_SESSION['studentId'])) {
    while (ob_get_level()) { ob_end_clean(); }
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'sessionExpired' => true,
        'message' => 'Unauthorized or session expired. Please refresh and log in again.'
    ]);
    exit();
}

$studentId = intval($_SESSION['studentId']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['requestFile'])) {
        while (ob_get_level()) { ob_end_clean(); }
        echo json_encode(['success' => false, 'message' => 'No file was received for upload.']);
        exit();
    }

    $requestId = isset($_POST['requestId']) ? intval($_POST['requestId']) : 0;
    $file = $_FILES['requestFile'];

    if ($requestId <= 0) {
        while (ob_get_level()) { ob_end_clean(); }
        echo json_encode(['success' => false, 'message' => 'Invalid file request ID.']);
        exit();
    }

    // 1. Fetch student's class and year group info
    $classQuery = "SELECT s.studentId, s.classId, s.yearGroupId AS studentDirectYearGroupId, c.classYearGroup 
                   FROM studentlogin s 
                   LEFT JOIN classes c ON s.classId = c.classId 
                   WHERE s.studentId = ?";
    $stmt = $connection->prepare($classQuery);
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $classResult = $stmt->get_result();

    if ($classResult->num_rows === 0) {
        while (ob_get_level()) { ob_end_clean(); }
        echo json_encode(['success' => false, 'message' => 'Student record not found in system.']);
        exit();
    }

    $studentRow = $classResult->fetch_assoc();
    $studentClassId = intval($studentRow['classId'] ?? 0);
    $studentDirectYearGroupId = intval($studentRow['studentDirectYearGroupId'] ?? 0);
    $studentYearGroupId = intval(!empty($studentRow['classYearGroup']) ? $studentRow['classYearGroup'] : $studentDirectYearGroupId);
    $stmt->close();

    // 2. Fetch file request details
    $reqQuery = "SELECT * FROM file_requests WHERE requestId = ? AND isActive = 1";
    $stmt = $connection->prepare($reqQuery);
    $stmt->bind_param("i", $requestId);
    $stmt->execute();
    $reqResult = $stmt->get_result();

    if ($reqResult->num_rows === 0) {
        while (ob_get_level()) { ob_end_clean(); }
        echo json_encode(['success' => false, 'message' => 'File collection request not found or is no longer active.']);
        exit();
    }

    $request = $reqResult->fetch_assoc();
    $stmt->close();

    // Verify target matches (either classId or yearGroupId, or open to all)
    $isTargeted = false;
    $reqClassId = !empty($request['classId']) ? intval($request['classId']) : 0;
    $reqYearGroupId = !empty($request['yearGroupId']) ? intval($request['yearGroupId']) : 0;

    if ($reqClassId > 0 && $reqClassId === $studentClassId) {
        $isTargeted = true;
    } elseif ($reqYearGroupId > 0 && ($reqYearGroupId === $studentYearGroupId || $reqYearGroupId === $studentDirectYearGroupId)) {
        $isTargeted = true;
    } elseif ($reqClassId === 0 && $reqYearGroupId === 0) {
        // Request targets all students
        $isTargeted = true;
    }

    if (!$isTargeted) {
        while (ob_get_level()) { ob_end_clean(); }
        echo json_encode(['success' => false, 'message' => 'Unauthorized: This file request is not targeted to your class or year group.']);
        exit();
    }

    // 3. Verify student hasn't already submitted
    $checkQuery = "SELECT submissionId FROM file_request_submissions WHERE requestId = ? AND studentId = ?";
    $stmt = $connection->prepare($checkQuery);
    $stmt->bind_param("ii", $requestId, $studentId);
    $stmt->execute();
    $checkResult = $stmt->get_result();

    if ($checkResult->num_rows > 0) {
        while (ob_get_level()) { ob_end_clean(); }
        echo json_encode(['success' => false, 'message' => 'You have already submitted a file for this collection request.']);
        exit();
    }
    $stmt->close();

    // 4. Validate file upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = 'File upload failed. ';
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errorMsg .= 'File exceeds the maximum upload size permitted by the server.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMsg .= 'File was only partially uploaded.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMsg .= 'No file was uploaded.';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $errorMsg .= 'Missing temporary folder on server.';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $errorMsg .= 'Failed to write file to disk.';
                break;
            default:
                $errorMsg .= 'Error code: ' . $file['error'];
        }
        while (ob_get_level()) { ob_end_clean(); }
        echo json_encode(['success' => false, 'message' => $errorMsg]);
        exit();
    }

    // Validate size
    $maxSizeMB = !empty($request['maxFileSizeMB']) ? intval($request['maxFileSizeMB']) : 10;
    $maxSizeBytes = $maxSizeMB * 1024 * 1024;
    if ($file['size'] > $maxSizeBytes) {
        while (ob_get_level()) { ob_end_clean(); }
        echo json_encode(['success' => false, 'message' => "File is too large. Maximum size allowed for this request is {$maxSizeMB} MB."]);
        exit();
    }

    // Validate extension
    $rawAllowed = explode(',', strtolower($request['allowedTypes'] ?? ''));
    $allowedExts = [];
    foreach ($rawAllowed as $rawExt) {
        $clean = ltrim(trim($rawExt), '.');
        if ($clean !== '') {
            $allowedExts[] = $clean;
        }
    }
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!empty($allowedExts) && !in_array($fileExt, $allowedExts, true)) {
        while (ob_get_level()) { ob_end_clean(); }
        echo json_encode(['success' => false, 'message' => 'Invalid file extension (.' . htmlspecialchars($fileExt) . '). Allowed extensions: ' . strtoupper(implode(', ', $allowedExts))]);
        exit();
    }

    // 5. Ensure storage directory exists
    $projectRoot = dirname(dirname(__DIR__));
    $baseUploadDir = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'file_requests';
    $storageDir = $baseUploadDir . DIRECTORY_SEPARATOR . $requestId . DIRECTORY_SEPARATOR;

    if (!is_dir($baseUploadDir)) {
        @mkdir($baseUploadDir, 0777, true);
        @chmod($baseUploadDir, 0777);
    }

    if (!is_dir($storageDir)) {
        @mkdir($storageDir, 0777, true);
        @chmod($storageDir, 0777);
    }

    if (!is_dir($storageDir)) {
        while (ob_get_level()) { ob_end_clean(); }
        echo json_encode([
            'success' => false, 
            'message' => 'Upload directory does not exist and could not be created. Please check folder permissions on server.'
        ]);
        exit();
    }

    if (!is_writable($storageDir)) {
        @chmod($storageDir, 0777);
        if (!is_writable($storageDir)) {
            while (ob_get_level()) { ob_end_clean(); }
            echo json_encode([
                'success' => false, 
                'message' => 'Upload directory is not writable. Please ensure server write permissions.'
            ]);
            exit();
        }
    }

    // Clean and generate unique filename
    $sanitizedOriginalName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
    $newFileName = time() . "_" . $studentId . "_" . $sanitizedOriginalName;
    $filePath = $storageDir . $newFileName;

    // 6. Save file to disk
    $moved = is_uploaded_file($file['tmp_name']) 
        ? move_uploaded_file($file['tmp_name'], $filePath)
        : @copy($file['tmp_name'], $filePath);

    if ($moved) {
        @chmod($filePath, 0666);

        // Check if submission is late
        $isLate = 0;
        if (!empty($request['dueDate'])) {
            $dueTime = strtotime($request['dueDate']);
            if ($dueTime !== false && time() > $dueTime) {
                $isLate = 1;
            }
        }

        // Relative path for database portability across OS/folders
        $relativeFilePath = 'uploads/file_requests/' . $requestId . '/' . $newFileName;

        // Insert into database
        $insertQuery = "INSERT INTO file_request_submissions (
            requestId, studentId, fileName, originalName, filePath, fileSize, isLate
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $connection->prepare($insertQuery);
        $fileSizeVal = intval($file['size']);
        $originalName = basename($file['name']);
        $stmt->bind_param("iisssii", $requestId, $studentId, $newFileName, $originalName, $relativeFilePath, $fileSizeVal, $isLate);

        if ($stmt->execute()) {
            while (ob_get_level()) { ob_end_clean(); }
            echo json_encode([
                'success' => true, 
                'message' => 'Your file has been submitted successfully!' . ($isLate ? ' (Submitted after due date)' : '')
            ]);
        } else {
            // Delete file if DB insert failed
            @unlink($filePath);
            while (ob_get_level()) { ob_end_clean(); }
            echo json_encode(['success' => false, 'message' => 'Failed to save submission records: ' . $connection->error]);
        }
        $stmt->close();
    } else {
        $lastErr = error_get_last();
        while (ob_get_level()) { ob_end_clean(); }
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to write file to disk. Error: ' . ($lastErr ? $lastErr['message'] : 'Permission denied or disk full.')
        ]);
    }
} else {
    while (ob_get_level()) { ob_end_clean(); }
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$connection->close();
?>
