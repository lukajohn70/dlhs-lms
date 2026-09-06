<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['adminId'])) {
    die('Unauthorized access');
}

$backupConfig = require_once '../../backup/backup_config.php';
$backupDir = $backupConfig['backup']['backup_dir'];

if (!isset($_GET['file'])) {
    die('No file specified');
}

$filename = basename($_GET['file']);
$filepath = $backupDir . $filename;

// Security check: ensure file is within backup directory
if (realpath(dirname($filepath)) !== realpath($backupDir)) {
    die('Invalid file path');
}

if (!file_exists($filepath)) {
    die('File not found');
}

// Download file
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filepath));

// Clear output buffer
ob_clean();
flush();

readfile($filepath);
exit;
?>

