<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";

$isStaff = isset($_SESSION['staffLoggedIn']) && $_SESSION['staffLoggedIn'] === "yes";
$isAdmin = isset($_SESSION['adminLoggedIn']) && $_SESSION['adminLoggedIn'] === "yes";

if (!$isStaff && !$isAdmin) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$currentStaffId = $isStaff ? (int)$_SESSION['staffId'] : 0;

$query = "SELECT s.staffId, CONCAT(s.surname, ' ', s.firstName) as fullName, s.username, s.passport,
                 GROUP_CONCAT(DISTINCT sub.subjectName ORDER BY sub.subjectName SEPARATOR ', ') as subjects
          FROM stafflogin s
          LEFT JOIN subject_teacher_assignment sta ON s.staffId = sta.teacherId
          LEFT JOIN subjects sub ON sta.subjectId = sub.subjectId
          GROUP BY s.staffId, s.surname, s.firstName, s.username, s.passport
          ORDER BY s.surname ASC, s.firstName ASC";

$result = $connection->query($query);
$staffList = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Teachers don't need to select themselves as a recipient
        if ($isStaff && (int)$row['staffId'] === $currentStaffId) {
            continue;
        }
        $staffList[] = [
            'staffId'  => (int)$row['staffId'],
            'fullName' => $row['fullName'],
            'username' => $row['username'],
            'passport' => !empty($row['passport']) ? $row['passport'] : '',
            'subjects' => !empty($row['subjects']) ? $row['subjects'] : 'General Staff'
        ];
    }
}

echo json_encode([
    'success' => true,
    'staff'   => $staffList
]);
