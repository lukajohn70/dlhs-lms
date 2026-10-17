<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/file_request_helper.php";

header('Content-Type: application/json');

if (!isset($_SESSION['studentLoggedIn']) || $_SESSION['studentLoggedIn'] !== "yes") {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

dlhsEnsureFileRequestTablesExist($connection);

$studentId = $_SESSION['studentId'];

// 1. Get the student's classId and their year group ID
$classQuery = "SELECT s.classId, c.classYearGroup AS yearGroupId
               FROM studentlogin s
               LEFT JOIN classes c ON s.classId = c.classId
               WHERE s.studentId = ?";
$stmt = $connection->prepare($classQuery);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$classResult = $stmt->get_result();

if ($classResult->num_rows === 0) {
    echo json_encode(['error' => 'Student class details not found']);
    exit();
}

$studentRow  = $classResult->fetch_assoc();
$classId     = $studentRow['classId'];
$yearGroupId = $studentRow['yearGroupId'];
$stmt->close();

if (!$classId) {
    echo json_encode([]);
    exit();
}

// 2. Fetch active requests that target:
//    - this student's specific class (r.classId = ?)  OR
//    - this student's year group (r.yearGroupId = ?)
$query = "SELECT r.*,
                 CONCAT(t.surname, ' ', t.firstName) AS teacherName,
                 s.subjectName,
                 sub.submissionId, sub.fileName, sub.originalName, sub.fileSize, sub.submittedAt, sub.isLate
          FROM file_requests r
          LEFT JOIN stafflogin  t   ON r.teacherId = t.staffId
          LEFT JOIN subjects    s   ON r.subjectId = s.subjectId
          LEFT JOIN file_request_submissions sub ON r.requestId = sub.requestId AND sub.studentId = ?
          WHERE r.isActive = 1
            AND (r.classId = ? OR r.yearGroupId = ?)
          ORDER BY r.dueDate ASC, r.created_at DESC";

$stmt = $connection->prepare($query);
$stmt->bind_param("iii", $studentId, $classId, $yearGroupId);
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = [
        'requestId'    => $row['requestId'],
        'title'        => $row['title'],
        'instructions' => $row['instructions'],
        'teacherName'  => $row['teacherName'] ?? 'Unknown Teacher',
        'subjectName'  => $row['subjectName'] ?? 'No Subject',
        'dueDate'      => $row['dueDate'],
        'allowedTypes' => $row['allowedTypes'],
        'maxFileSizeMB'=> $row['maxFileSizeMB'],
        'submitted'    => ($row['submissionId'] !== null),
        'submission'   => ($row['submissionId'] !== null) ? [
            'submissionId' => $row['submissionId'],
            'fileName'     => $row['fileName'],
            'originalName' => $row['originalName'],
            'fileSize'     => $row['fileSize'],
            'submittedAt'  => $row['submittedAt'],
            'isLate'       => intval($row['isLate'])
        ] : null
    ];
}

$stmt->close();
$connection->close();

echo json_encode($requests);
?>
