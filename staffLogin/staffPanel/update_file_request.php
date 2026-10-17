<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/file_request_helper.php";

header('Content-Type: application/json');

if (!isset($_SESSION['staffLoggedIn']) || $_SESSION['staffLoggedIn'] !== "yes") {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

dlhsEnsureFileRequestTablesExist($connection);

$staffId = $_SESSION['staffId'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId     = isset($_POST['requestId'])     ? intval($_POST['requestId'])           : 0;
    $title         = isset($_POST['title'])         ? trim((string)$_POST['title'])         : '';
    $instructions  = isset($_POST['instructions'])  ? trim((string)$_POST['instructions'])  : '';
    $targetType    = isset($_POST['targetType'])     ? trim((string)$_POST['targetType'])    : 'class';
    $classId       = isset($_POST['classId'])        && $_POST['classId'] !== '' ? intval($_POST['classId'])       : null;
    $yearGroupId   = isset($_POST['yearGroupId'])    && $_POST['yearGroupId'] !== '' ? intval($_POST['yearGroupId']) : null;
    $subjectId     = isset($_POST['subjectId'])      && $_POST['subjectId'] !== '' ? intval($_POST['subjectId'])    : null;
    $dueDate       = isset($_POST['dueDate'])        && $_POST['dueDate'] !== '' ? trim((string)$_POST['dueDate']) : null;
    $allowedTypes  = isset($_POST['allowedTypes'])   ? trim((string)$_POST['allowedTypes'])  : 'pdf,doc,docx,jpg,png,zip';
    $maxFileSizeMB = isset($_POST['maxFileSizeMB'])  ? intval($_POST['maxFileSizeMB'])        : 10;

    if ($requestId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
        exit();
    }

    if ($title === '') {
        echo json_encode(['success' => false, 'message' => 'Title is required.']);
        exit();
    }

    // Verify ownership
    $checkQuery = "SELECT requestId FROM `file_requests` WHERE `requestId` = ? AND `teacherId` = ? AND `isActive` = 1";
    $stmt = $connection->prepare($checkQuery);
    $stmt->bind_param("ii", $requestId, $staffId);
    $stmt->execute();
    $checkResult = $stmt->get_result();

    if ($checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'File request not found or you do not have permission to edit it.']);
        $stmt->close();
        exit();
    }
    $stmt->close();

    // Resolve target
    if ($targetType === 'yeargroup') {
        if ($yearGroupId === null) {
            echo json_encode(['success' => false, 'message' => 'Please select a Year Group.']);
            exit();
        }
        $classIdVal     = 'NULL';
        $yearGroupIdVal = intval($yearGroupId);
    } else {
        if ($classId === null) {
            echo json_encode(['success' => false, 'message' => 'Target Class is required.']);
            exit();
        }
        $classIdVal     = intval($classId);
        $yearGroupIdVal = 'NULL';
    }

    $titleEsc        = mysqli_real_escape_string($connection, $title);
    $instructionsEsc = mysqli_real_escape_string($connection, $instructions);
    $allowedTypesEsc = mysqli_real_escape_string($connection, $allowedTypes);
    $subjectIdVal    = $subjectId !== null ? intval($subjectId) : 'NULL';
    $dueDateVal      = $dueDate !== null ? "'" . mysqli_real_escape_string($connection, $dueDate) . "'" : 'NULL';

    $query = "UPDATE `file_requests` SET 
        `title` = '$titleEsc', 
        `instructions` = '$instructionsEsc', 
        `classId` = $classIdVal, 
        `yearGroupId` = $yearGroupIdVal, 
        `subjectId` = $subjectIdVal, 
        `dueDate` = $dueDateVal, 
        `allowedTypes` = '$allowedTypesEsc', 
        `maxFileSizeMB` = '$maxFileSizeMB'
        WHERE `requestId` = $requestId AND `teacherId` = $staffId";

    if ($connection->query($query)) {
        $targetLabel = ($targetType === 'yeargroup') ? 'entire year group' : 'class';
        echo json_encode(['success' => true, 'message' => "File request updated successfully for the $targetLabel!"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update request: ' . $connection->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$connection->close();
?>
