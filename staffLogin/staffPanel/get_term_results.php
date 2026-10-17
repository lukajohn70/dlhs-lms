<?php
// get_term_results.php — returns existing scores for a class/subject/term/session
session_start();
require_once 'userExpiredSession.php';
if (!isset($_SESSION['staffLoggedIn'])) { echo json_encode([]); exit; }
require_once '../../db_connection/dlhs_db_connection.php';

header('Content-Type: application/json');

$classId   = intval($_POST['classId']        ?? 0);
$subjectId = intval($_POST['subjectId']       ?? 0);
$term      = intval($_POST['academicTerm']    ?? 0);
$session   = trim($_POST['academicSession']   ?? '');
$type      = ($_POST['resultType'] ?? 'end_of_term') === 'mid_term' ? 'mid_term' : 'end_of_term';

if (!$classId || !$subjectId || !$session) { echo json_encode([]); exit; }

$sql = "SELECT r.studentId, 
               r.academicTerm,
               MAX(r.assignmentScore) as assignmentScore, 
               MAX(r.projectScore) as projectScore, 
               MAX(r.midTermTest) as midTermTest,
               MAX(r.test1Score) as test1Score, 
               MAX(r.test2Score) as test2Score, 
               MAX(r.examScore) as examScore
        FROM dlhs_term_results r
        JOIN studentlogin s ON s.studentId = r.studentId
        WHERE s.classId = ?
          AND r.subjectId = ?
          AND r.academicSession = ?
        GROUP BY r.studentId, r.academicTerm";
$stmt = $connection->prepare($sql);
$stmt->bind_param('iis', $classId, $subjectId, $session);
$stmt->execute();
$res  = $stmt->get_result();
$rows = [];
while ($row = $res->fetch_assoc()) $rows[] = $row;
$stmt->close();
echo json_encode($rows);
