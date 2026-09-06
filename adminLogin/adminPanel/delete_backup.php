<?php
session_start();
	error_reporting(0);
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['adminId'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$backupConfig = require_once '../../backup/backup_config.php';
$backupDir = $backupConfig['backup']['backup_dir'];

if (!isset($_GET['file'])) {
    echo json_encode(['success' => false, 'message' => 'No file specified']);
    exit;
}

$filename = basename($_GET['file']);
$filepath = $backupDir . $filename;

// Security check: ensure file is within backup directory
if (realpath(dirname($filepath)) !== realpath($backupDir)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file path']);
    exit;
}

if (!file_exists($filepath)) {
    echo json_encode(['success' => false, 'message' => 'File not found']);
    exit;
}

// Delete file or directory
try {
    if (is_dir($filepath)) {
        deleteDirectory($filepath);
    } else {
        unlink($filepath);
    }
    
    echo json_encode(['success' => true, 'message' => 'Backup deleted successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error deleting backup: ' . $e->getMessage()]);
}

function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = array_diff(scandir($dir), ['.', '..']);
    
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? deleteDirectory($path) : unlink($path);
    }
    
    return rmdir($dir);
}
?>

