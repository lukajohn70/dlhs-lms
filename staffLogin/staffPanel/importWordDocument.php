<?php
session_start();
require_once 'sessionTime.php';

header('Content-Type: application/json');

if ((time() - $_SESSION['staffLast_login']) > $allottedTime) {
    require_once 'unsetSessions.php';
    echo json_encode(array('success' => false, 'message' => 'Session expired.'));
    exit;
}

if (!isset($_SESSION['staffId'])) {
    echo json_encode(array('success' => false, 'message' => 'Unauthorized request.'));
    exit;
}

require_once "../../scripts/docx_import_helper.php";

if (empty($_FILES['file']['name']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
    echo json_encode(array('success' => false, 'message' => 'Select a Word .docx file first.'));
    exit;
}

$extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
if ($extension !== 'docx') {
    echo json_encode(array('success' => false, 'message' => 'Only Word .docx files are supported.'));
    exit;
}

$result = dlhsImportDocxHtml(
    $_FILES['file']['tmp_name'],
    dirname(__DIR__, 2) . '/questUploadImages/questionImages',
    '../../questUploadImages/questionImages'
);

echo json_encode($result);
