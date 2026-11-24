<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/userExpiredSession.php";
require_once __DIR__ . "/../../db_connection/dlhs_db_connection.php";
require_once __DIR__ . "/../../scripts/file_request_helper.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['studentLoggedIn']) || $_SESSION['studentLoggedIn'] !== "yes" || empty($_SESSION['studentId'])) {
    while (ob_get_level()) { ob_end_clean(); }
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access or session expired']);
    exit();
}

dlhsEnsureFileRequestTablesExist($connection);

$studentId = intval($_SESSION['studentId']);

// 1. Get student's classId and year group ID
$classQuery = "SELECT s.studentId, s.classId, s.yearGroupId AS studentDirectYearGroupId, c.classYearGroup 
               FROM studentlogin s
               LEFT JOIN classes c ON s.classId = c.classId
               WHERE s.studentId = ?";
$stmt = $connection->prepare($classQuery);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$classResult = $stmt->get_result();

if ($classResult->num_rows === 0) {
    $stmt->close();
    while (ob_get_level()) { ob_end_clean(); }
    echo json_encode([]);
    exit();
}

$studentRow  = $classResult->fetch_assoc();
$classId     = intval($studentRow['classId'] ?? 0);
$directYearGroupId = intval($studentRow['studentDirectYearGroupId'] ?? 0);
$yearGroupId = intval(!empty($studentRow['classYearGroup']) ? $studentRow['classYearGroup'] : $directYearGroupId);
$stmt->close();

// 2. Fetch active requests targeting:
//    - this student's specific class (r.classId = ?) OR
//    - this student's year group (r.yearGroupId = ?) OR
//    - open to all classes & year groups (classId IS NULL/0 AND yearGroupId IS NULL/0)
$query = "SELECT r.*,
                 CONCAT(t.surname, ' ', t.firstName) AS teacherName,
                 s.subjectName,
                 sub.submissionId, sub.fileName, sub.originalName, sub.fileSize, sub.submittedAt, sub.isLate
          FROM file_requests r
          LEFT JOIN stafflogin  t   ON r.teacherId = t.staffId
          LEFT JOIN subjects    s   ON r.subjectId = s.subjectId
          LEFT JOIN file_request_submissions sub ON r.requestId = sub.requestId AND sub.studentId = ?
          WHERE r.isActive = 1
            AND (
                (? > 0 AND r.classId = ?)
                OR (? > 0 AND (r.yearGroupId = ? OR r.yearGroupId = ?))
                OR ((r.classId IS NULL OR r.classId = 0) AND (r.yearGroupId IS NULL OR r.yearGroupId = 0))
            )
          ORDER BY (sub.submissionId IS NOT NULL) ASC, r.dueDate ASC, r.created_at DESC";

$stmt = $connection->prepare($query);
$stmt->bind_param("iiiiii", $studentId, $classId, $classId, $yearGroupId, $yearGroupId, $directYearGroupId);
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = [
        'requestId'    => (int)$row['requestId'],
        'title'        => $row['title'],
        'instructions' => $row['instructions'],
        'teacherName'  => $row['teacherName'] ?? 'Subject Teacher',
        'subjectName'  => $row['subjectName'] ?? 'General',
        'dueDate'      => $row['dueDate'],
        'allowedTypes' => $row['allowedTypes'],
        'maxFileSizeMB'=> (int)($row['maxFileSizeMB'] ?: 10),
        'submitted'    => ($row['submissionId'] !== null),
        'submission'   => ($row['submissionId'] !== null) ? [
            'submissionId' => (int)$row['submissionId'],
            'fileName'     => $row['fileName'],
            'originalName' => $row['originalName'],
            'fileSize'     => (int)$row['fileSize'],
            'submittedAt'  => $row['submittedAt'],
            'isLate'       => intval($row['isLate'])
        ] : null
    ];
}

$stmt->close();
$connection->close();

while (ob_get_level()) { ob_end_clean(); }
echo json_encode($requests);
?>
