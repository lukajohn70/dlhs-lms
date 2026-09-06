<?php
// save_assessment.php — AJAX endpoint to save/update a student's term assessments
ob_start(); // Buffer ALL output so no stray warnings can corrupt the JSON
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once 'userExpiredSession.php';

ob_clean(); // Discard any warnings that slipped out above
header('Content-Type: application/json');

if (!isset($_SESSION['staffLoggedIn'])) {
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

require_once '../../db_connection/dlhs_db_connection.php';

$studentId = intval($_POST['studentId']      ?? 0);
$term      = intval($_POST['academicTerm']   ?? 0);
$session   = trim($_POST['academicSession']  ?? '');

if (!$studentId || !$term || !$session) {
    echo json_encode(['success' => false, 'msg' => 'Missing required fields: studentId=' . $studentId . ' term=' . $term . ' session=' . $session]);
    exit;
}

$g = function($k) {
    return isset($_POST[$k]) && $_POST[$k] !== '' ? intval($_POST[$k]) : null;
};
$gs = function($k) {
    return isset($_POST[$k]) && $_POST[$k] !== '' ? trim($_POST[$k]) : null;
};

$fields = [
    'totalDays','presentDays',
    'punctuality','neatness','politeness','honesty','teamSpirit','leadership',
    'helpingOthers','emotionalStability','health','attitudeToWork',
    'attentiveness','perseverance','spokenEnglish',
    'handwriting','verbalFluency','sports','handlingTools','musical','drawingPainting'
];
$textFields = ['classTeacherComment','principalRemark','midTermComment','endOfTermComment'];

$sets  = [];
$types = '';
$vals  = [];

foreach ($fields as $f) {
    $v = $g($f);
    $sets[]  = "`$f` = ?";
    $types  .= 'i';
    $vals[]  = $v;
}
foreach ($textFields as $f) {
    $v = $gs($f);
    $sets[]  = "`$f` = ?";
    $types  .= 's';
    $vals[]  = $v;
}

$setCols = implode(', ', $fields) . ', ' . implode(', ', $textFields);
$setPlaceholders = rtrim(str_repeat('?,', count($fields) + count($textFields)), ',');

$sql = "INSERT INTO dlhs_student_assessments
            (studentId, academicTerm, academicSession, $setCols)
        VALUES (?, ?, ?, $setPlaceholders)
        ON DUPLICATE KEY UPDATE " . implode(', ', $sets);

$stmt = $connection->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'msg' => 'SQL prepare error: ' . $connection->error]);
    exit;
}

// bind: studentId(i), term(i), session(s) + all values twice (once for INSERT, once for UPDATE)
$bindTypes = 'iis' . $types . $types;
$bindVals  = array_merge([$studentId, $term, $session], $vals, $vals);

$stmt->bind_param($bindTypes, ...$bindVals);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'msg' => $stmt->error]);
}
$stmt->close();
