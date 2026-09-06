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

$staffId = $_SESSION['staffId'];

$query = "SELECT r.*,
                 CASE
                    WHEN r.yearGroupId IS NOT NULL THEN CONCAT('Entire ', yg2.yearGroupName)
                    ELSE CONCAT(yg.yearGroupName, ' - ', c.className)
                 END AS className,
                 s.subjectName,
                 (SELECT COUNT(*) FROM file_request_submissions WHERE requestId = r.requestId) AS submissionCount
          FROM file_requests r
          LEFT JOIN classes c  ON r.classId = c.classId
          LEFT JOIN yeargroup yg  ON c.classYearGroup = yg.yearGroupId
          LEFT JOIN yeargroup yg2 ON r.yearGroupId = yg2.yearGroupId
          LEFT JOIN subjects  s  ON r.subjectId = s.subjectId
          WHERE r.teacherId = ? AND r.isActive = 1
          ORDER BY r.created_at DESC";

$stmt = $connection->prepare($query);
$stmt->bind_param("i", $staffId);
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = [
        'requestId'      => $row['requestId'],
        'title'          => $row['title'],
        'instructions'   => $row['instructions'],
        'classId'        => $row['classId'],
        'yearGroupId'    => $row['yearGroupId'],
        'className'      => $row['className'] ?? 'All Classes',
        'subjectId'      => $row['subjectId'],
        'subjectName'    => $row['subjectName'] ?? 'No Subject',
        'dueDate'        => $row['dueDate'],
        'allowedTypes'   => $row['allowedTypes'],
        'maxFileSizeMB'  => $row['maxFileSizeMB'],
        'created_at'     => $row['created_at'],
        'submissionCount'=> intval($row['submissionCount'])
    ];
}

$stmt->close();
$connection->close();

echo json_encode($requests);
?>
