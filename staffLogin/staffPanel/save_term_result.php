<?php
// save_term_result.php — AJAX endpoint to save/update a single student's score
session_start();
require_once 'userExpiredSession.php';
if (!isset($_SESSION['staffLoggedIn'])) { echo json_encode(['success'=>false,'msg'=>'Unauthorized']); exit; }
require_once '../../db_connection/dlhs_db_connection.php';

header('Content-Type: application/json');

$studentId   = intval($_POST['studentId']   ?? 0);
$subjectId   = intval($_POST['subjectId']   ?? 0);
$term        = intval($_POST['academicTerm'] ?? 0);
$session     = trim($_POST['academicSession'] ?? '');
$resultType  = ($_POST['resultType'] ?? 'end_of_term') === 'mid_term' ? 'mid_term' : 'end_of_term';

if (!$studentId || !$subjectId || !$term || !$session) {
    echo json_encode(['success'=>false,'msg'=>'Missing required fields']);
    exit;
}

// Back-end authorization: Ensure the teacher is assigned to this subject in the student's class
$staffId = intval($_SESSION['staffId']);

// Get the student's classId
$classCheckStmt = $connection->prepare("SELECT classId FROM studentlogin WHERE studentId = ?");
$classCheckStmt->bind_param('i', $studentId);
$classCheckStmt->execute();
$classCheckRes = $classCheckStmt->get_result();
$studentClass = $classCheckRes->fetch_assoc();
$classCheckStmt->close();

if (!$studentClass) {
    echo json_encode(['success' => false, 'msg' => 'Student not found']);
    exit;
}

$studentClassId = intval($studentClass['classId']);

// Verify assignment
$assignCheckStmt = $connection->prepare("
    SELECT 1 FROM subject_teacher_assignment 
    WHERE teacherId = ? AND subjectId = ? AND classId = ?
");
$assignCheckStmt->bind_param('iii', $staffId, $subjectId, $studentClassId);
$assignCheckStmt->execute();
$assignCheckRes = $assignCheckStmt->get_result();
$hasAssignment = ($assignCheckRes->num_rows > 0);
$assignCheckStmt->close();

if (!$hasAssignment) {
    echo json_encode(['success' => false, 'msg' => 'You are not authorized to record scores for this subject and class.']);
    exit;
}

if ($resultType === 'mid_term') {
    $assign  = isset($_POST['assignmentScore']) && $_POST['assignmentScore'] !== '' ? floatval($_POST['assignmentScore']) : null;
    $project = isset($_POST['projectScore'])    && $_POST['projectScore']    !== '' ? floatval($_POST['projectScore'])    : null;
    $midTest = isset($_POST['midTermTest'])      && $_POST['midTermTest']      !== '' ? floatval($_POST['midTermTest'])      : null;

    // Backend Validation: enforce upper bounds for mid-term components
    if (($assign !== null && ($assign < 0 || $assign > 5)) ||
        ($project !== null && ($project < 0 || $project > 5)) ||
        ($midTest !== null && ($midTest < 0 || $midTest > 10))) {
        echo json_encode(['success' => false, 'msg' => 'Invalid score: exceeds maximum allowed limits (Assignment: 5, Project: 5, Test: 10).']);
        exit;
    }

    $sql = "INSERT INTO dlhs_term_results
                (studentId, subjectId, academicTerm, academicSession, resultType, assignmentScore, projectScore, midTermTest)
            VALUES (?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
                assignmentScore = VALUES(assignmentScore),
                projectScore    = VALUES(projectScore),
                midTermTest     = VALUES(midTermTest)";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param('iiissddd', $studentId, $subjectId, $term, $session, $resultType, $assign, $project, $midTest);
} else {
    // Dynamically calculate test1Score (CA 1 / CAT 1) on the server side using the mid-term components
    $midStmt = $connection->prepare("
        SELECT assignmentScore, projectScore, midTermTest 
        FROM dlhs_term_results 
        WHERE studentId = ? AND subjectId = ? AND academicTerm = ? AND academicSession = ? AND resultType = 'mid_term'
    ");
    $midStmt->bind_param('iiis', $studentId, $subjectId, $term, $session);
    $midStmt->execute();
    $midRes = $midStmt->get_result();
    $midData = $midRes->fetch_assoc();
    $midStmt->close();

    if ($midData) {
        $as = $midData['assignmentScore'] !== null ? floatval($midData['assignmentScore']) : 0;
        $pr = $midData['projectScore']    !== null ? floatval($midData['projectScore']) : 0;
        $mt = $midData['midTermTest']     !== null ? floatval($midData['midTermTest']) : 0;
        $t1 = $as + $pr + $mt;
    } else {
        $t1 = null;
    }

    $t2   = isset($_POST['test2Score']) && $_POST['test2Score'] !== '' ? floatval($_POST['test2Score']) : null;
    $exam = isset($_POST['examScore'])  && $_POST['examScore']  !== '' ? floatval($_POST['examScore'])  : null;

    // Backend Validation: enforce upper bounds for end-of-term components
    if (($t2 !== null && ($t2 < 0 || $t2 > 20)) ||
        ($exam !== null && ($exam < 0 || $exam > 60))) {
        echo json_encode(['success' => false, 'msg' => 'Invalid score: exceeds maximum allowed limits (CA 2: 20, Exam: 60).']);
        exit;
    }

    $sql = "INSERT INTO dlhs_term_results
                (studentId, subjectId, academicTerm, academicSession, resultType, test1Score, test2Score, examScore)
            VALUES (?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
                test1Score = VALUES(test1Score),
                test2Score = VALUES(test2Score),
                examScore  = VALUES(examScore)";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param('iiissddd', $studentId, $subjectId, $term, $session, $resultType, $t1, $t2, $exam);
}

if ($stmt->execute()) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'msg'=>$stmt->error]);
}
$stmt->close();
