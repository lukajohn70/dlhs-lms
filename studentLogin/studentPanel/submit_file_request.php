<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/file_request_helper.php";

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['studentLoggedIn']) || $_SESSION['studentLoggedIn'] !== "yes") {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$studentId = $_SESSION['studentId'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['requestFile'])) {
    $requestId = isset($_POST['requestId']) ? intval($_POST['requestId']) : 0;
    $file = $_FILES['requestFile'];

    if ($requestId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
        exit();
    }

    // 1. Fetch student's classId and yearGroupId
    $classQuery = "SELECT s.classId, c.classYearGroup 
                  FROM studentlogin s 
                  INNER JOIN classes c ON s.classId = c.classId 
                  WHERE s.studentId = ?";
    $stmt = $connection->prepare($classQuery);
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $classResult = $stmt->get_result();

    if ($classResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Student class details not found']);
        exit();
    }

    $studentRow = $classResult->fetch_assoc();
    $studentClassId = $studentRow['classId'];
    $studentYearGroupId = $studentRow['classYearGroup'];
    $stmt->close();

    // 2. Fetch file request details
    $reqQuery = "SELECT * FROM file_requests WHERE requestId = ? AND isActive = 1";
    $stmt = $connection->prepare($reqQuery);
    $stmt->bind_param("i", $requestId);
    $stmt->execute();
    $reqResult = $stmt->get_result();

    if ($reqResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'File collection request not found or inactive.']);
        exit();
    }

    $request = $reqResult->fetch_assoc();
    $stmt->close();

    // Verify target matches (either classId or yearGroupId)
    $isTargeted = false;
    if (!empty($request['classId']) && $request['classId'] == $studentClassId) {
        $isTargeted = true;
    } elseif (!empty($request['yearGroupId']) && $request['yearGroupId'] == $studentYearGroupId) {
        $isTargeted = true;
    }

    if (!$isTargeted) {
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
        echo json_encode(['success' => false, 'message' => 'You have already submitted a file for this collection request.']);
        exit();
    }
    $stmt->close();

    // 4. Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'File upload failed. Error code: ' . $file['error']]);
        exit();
    }

    // Validate size
    $maxSizeMB = $request['maxFileSizeMB'] ? intval($request['maxFileSizeMB']) : 10;
    $maxSizeBytes = $maxSizeMB * 1024 * 1024;
    // if ($file['size'] > $maxSizeBytes) {
    //     echo json_encode(['success' => false, 'message' => 'File is too large. Maximum size is ' . $maxSizeMB . ' MB.']);
    //     exit();
    // }

    // Validate extension
    $allowedExts = array_map('trim', explode(',', strtolower($request['allowedTypes'])));
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($fileExt, $allowedExts)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file extension. Allowed extensions: ' . strtoupper(implode(', ', $allowedExts))]);
        exit();
    }

    // 5. Create storage directory with robust error checking and detailed diagnostics
    $storageDir = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'file_requests' . DIRECTORY_SEPARATOR . $requestId . DIRECTORY_SEPARATOR;
    
    $dirExists = is_dir($storageDir);
    if (!$dirExists) {
        $dirExists = @mkdir($storageDir, 0777, true);
        if ($dirExists) {
            @chmod($storageDir, 0777);
        }
    }
    
    if (!$dirExists) {
        echo json_encode([
            'success' => false, 
            'message' => 'Upload directory does not exist and could not be created: ' . $storageDir . '. Please check parent folder permissions.'
        ]);
        exit();
    }
    
    if (!is_writable($storageDir)) {
        @chmod($storageDir, 0777);
        if (!is_writable($storageDir)) {
            $currentUser = function_exists('posix_getpwuid') && function_exists('posix_geteuid') 
                ? posix_getpwuid(posix_geteuid())['name'] 
                : get_current_user();
            echo json_encode([
                'success' => false, 
                'message' => 'Upload directory is not writable: ' . $storageDir . '. Executing process user: ' . $currentUser . '. Please ensure directory permissions allow writes.'
            ]);
            exit();
        }
    }

    // Clean and generate unique filename
    $sanitizedOriginalName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
    $newFileName = time() . "_" . $studentId . "_" . $sanitizedOriginalName;
    $filePath = $storageDir . $newFileName;

    // 6. Save file to disk
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        
        // Check if submission is late
        $isLate = 0;
        if ($request['dueDate']) {
            $dueTime = strtotime($request['dueDate']);
            if (time() > $dueTime) {
                $isLate = 1;
            }
        }

        // Insert into database
        $insertQuery = "INSERT INTO file_request_submissions (
            requestId, studentId, fileName, originalName, filePath, fileSize, isLate
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $connection->prepare($insertQuery);
        $fileSizeVal = intval($file['size']);
        $stmt->bind_param("iisssii", $requestId, $studentId, $newFileName, $file['name'], $filePath, $fileSizeVal, $isLate);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'Your file has been submitted successfully!' . ($isLate ? ' (Late submission)' : '')
            ]);
        } else {
            // Delete file if DB insert failed
            unlink($filePath);
            echo json_encode(['success' => false, 'message' => 'Failed to save submission records: ' . $connection->error]);
        }
        $stmt->close();
    } else {
        $lastErr = error_get_last();
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to write file to disk. Check directory permissions. Error: ' . ($lastErr ? $lastErr['message'] : 'Unknown error'),
            'debug' => [
                'tmp_name' => $file['tmp_name'],
                'destination' => $filePath,
                'is_uploaded' => is_uploaded_file($file['tmp_name'])
            ]
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid file or request data.']);
}

$connection->close();
?>
