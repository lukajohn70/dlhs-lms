<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/file_request_helper.php";

header('Content-Type: application/json');

if (!isset($_SESSION['staffLoggedIn']) || $_SESSION['staffLoggedIn'] !== "yes") {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

dlhsEnsureFileRequestTablesExist($connection);

$staffId   = $_SESSION['staffId'];
$requestId = isset($_GET['requestId']) ? intval($_GET['requestId']) : 0;

if ($requestId <= 0) {
    echo json_encode(['error' => 'Invalid Request ID']);
    exit();
}

// 1. Fetch request details and verify ownership
$reqQuery = "SELECT r.*,
             CASE
                WHEN r.yearGroupId IS NOT NULL THEN CONCAT('Entire ', yg2.yearGroupName)
                ELSE CONCAT(yg.yearGroupName, ' - ', c.className)
             END AS displayName,
             s.subjectName
             FROM file_requests r
             LEFT JOIN classes c   ON r.classId = c.classId
             LEFT JOIN yeargroup yg  ON c.classYearGroup = yg.yearGroupId
             LEFT JOIN yeargroup yg2 ON r.yearGroupId = yg2.yearGroupId
             LEFT JOIN subjects s   ON r.subjectId = s.subjectId
             WHERE r.requestId = ? AND r.teacherId = ? AND r.isActive = 1";

$stmt = $connection->prepare($reqQuery);
$stmt->bind_param("ii", $requestId, $staffId);
$stmt->execute();
$reqResult = $stmt->get_result();

if ($reqResult->num_rows === 0) {
    echo json_encode(['error' => 'Request not found or unauthorized']);
    exit();
}

$requestDetails = $reqResult->fetch_assoc();
$stmt->close();

$classId     = $requestDetails['classId'];
$yearGroupId = $requestDetails['yearGroupId'];
$isYearGroup = !empty($yearGroupId);

// 2. Fetch students — either all in the year group or just the single class
if ($isYearGroup) {
    // All students across all classes in the year group
    $subQuery = "SELECT s.studentId, s.surname, s.firstName, s.middleName,
                        CONCAT(yg.yearGroupName, ' - ', c.className) AS className,
                        sub.submissionId, sub.fileName, sub.originalName, sub.fileSize, sub.submittedAt, sub.isLate
                 FROM studentlogin s
                 INNER JOIN classes c    ON s.classId = c.classId
                 INNER JOIN yeargroup yg ON c.classYearGroup = yg.yearGroupId
                 LEFT JOIN  file_request_submissions sub ON s.studentId = sub.studentId AND sub.requestId = ?
                 WHERE c.classYearGroup = ?
                 ORDER BY c.className, s.surname, s.firstName";
    $stmt = $connection->prepare($subQuery);
    $stmt->bind_param("ii", $requestId, $yearGroupId);
} else {
    // Single class
    $subQuery = "SELECT s.studentId, s.surname, s.firstName, s.middleName,
                        CONCAT(yg.yearGroupName, ' - ', c.className) AS className,
                        sub.submissionId, sub.fileName, sub.originalName, sub.fileSize, sub.submittedAt, sub.isLate
                 FROM studentlogin s
                 INNER JOIN classes c    ON s.classId = c.classId
                 INNER JOIN yeargroup yg ON c.classYearGroup = yg.yearGroupId
                 LEFT JOIN  file_request_submissions sub ON s.studentId = sub.studentId AND sub.requestId = ?
                 WHERE s.classId = ?
                 ORDER BY s.surname, s.firstName";
    $stmt = $connection->prepare($subQuery);
    $stmt->bind_param("ii", $requestId, $classId);
}

$stmt->execute();
$subResult = $stmt->get_result();

$students = [];
while ($row = $subResult->fetch_assoc()) {
    $students[] = [
        'studentId'    => $row['studentId'],
        'surname'      => $row['surname'],
        'firstName'    => $row['firstName'],
        'middleName'   => $row['middleName'],
        'className'    => $row['className'],
        'submitted'    => ($row['submissionId'] !== null),
        'submissionId' => $row['submissionId'],
        'fileName'     => $row['fileName'],
        'originalName' => $row['originalName'],
        'fileSize'     => $row['fileSize'],
        'submittedAt'  => $row['submittedAt'],
        'isLate'       => intval($row['isLate'])
    ];
}

$stmt->close();
$connection->close();

echo json_encode([
    'request' => [
        'requestId'     => $requestDetails['requestId'],
        'title'         => $requestDetails['title'],
        'instructions'  => $requestDetails['instructions'],
        'className'     => $requestDetails['displayName'],
        'subjectName'   => $requestDetails['subjectName'] ?? 'No Subject',
        'dueDate'       => $requestDetails['dueDate'],
        'allowedTypes'  => $requestDetails['allowedTypes'],
        'maxFileSizeMB' => $requestDetails['maxFileSizeMB'],
        'isYearGroup'   => $isYearGroup
    ],
    'students' => $students
]);
?>
