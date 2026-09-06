<?php
/**
 * DLHS Automated Backup System
 * 
 * This script performs automated backups of:
 * 1. MySQL Database
 * 2. Uploaded Files
 * 3. Critical Configuration Files
 * 
 * Run this script via Windows Task Scheduler every 30 minutes
 */

// Prevent direct browser access for security
if (php_sapi_name() !== 'cli' && !isset($_GET['manual_run'])) {
    die('This script can only be run from command line or with manual_run parameter.');
}

// Load configuration
$config = require_once __DIR__ . '/backup_config.php';

// Create necessary directories
if (!is_dir($config['backup']['backup_dir'])) {
    mkdir($config['backup']['backup_dir'], 0755, true);
}

if (!is_dir(dirname($config['logging']['log_file']))) {
    mkdir(dirname($config['logging']['log_file']), 0755, true);
}

// Initialize logger
class BackupLogger {
    private $logFile;
    private $logLevel;
    
    public function __construct($logFile, $logLevel = 'INFO') {
        $this->logFile = $logFile;
        $this->logLevel = $logLevel;
    }
    
    public function log($message, $level = 'INFO') {
        $levels = ['DEBUG' => 0, 'INFO' => 1, 'WARNING' => 2, 'ERROR' => 3];
        
        if ($levels[$level] >= $levels[$this->logLevel]) {
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
            file_put_contents($this->logFile, $logMessage, FILE_APPEND);
            
            // Also echo to console if running in CLI
            if (php_sapi_name() === 'cli') {
                echo $logMessage;
            }
        }
    }
}

$logger = new BackupLogger($config['logging']['log_file'], $config['logging']['log_level']);

/**
 * Main Backup Function
 */
function performBackup($config, $logger) {
    $logger->log('Starting automated backup process...', 'INFO');
    $startTime = microtime(true);
    
    try {
        // Generate backup filename with timestamp
        $timestamp = date('Y-m-d_H-i-s');
        $backupName = "dlhs_backup_{$timestamp}";
        $backupDir = $config['backup']['backup_dir'] . $backupName . '/';
        
        // Create backup directory
        if (!mkdir($backupDir, 0755, true)) {
            throw new Exception("Failed to create backup directory: $backupDir");
        }
        
        $logger->log("Created backup directory: $backupDir", 'INFO');
        
        // 1. Backup Database
        $logger->log('Starting database backup...', 'INFO');
        $dbBackupFile = backupDatabase($config, $backupDir, $logger);
        $logger->log("Database backup completed: $dbBackupFile", 'INFO');
        
        // 2. Backup Uploads (if enabled)
        if ($config['backup']['backup_uploads']) {
            $logger->log('Starting uploads backup...', 'INFO');
            $uploadsBackupFile = backupUploads($config, $backupDir, $logger);
            $logger->log("Uploads backup completed: $uploadsBackupFile", 'INFO');
        }
        
        // 2b. Backup Question Images
        $logger->log('Starting question images backup...', 'INFO');
        $questImagesBackupFile = backupQuestionImages($config, $backupDir, $logger);
        $logger->log("Question images backup completed: $questImagesBackupFile", 'INFO');
        
        // 3. Backup Configuration Files
        $logger->log('Starting configuration backup...', 'INFO');
        $configBackupFile = backupConfigFiles($config, $backupDir, $logger);
        $logger->log("Configuration backup completed: $configBackupFile", 'INFO');
        
        // 4. Compress backup (if enabled)
        if ($config['backup']['compress']) {
            $logger->log('Compressing backup...', 'INFO');
            $compressedFile = compressBackup($config, $backupDir, $backupName, $logger);
            
            // Remove uncompressed backup directory
            deleteDirectory($backupDir);
            $logger->log("Compressed backup created: $compressedFile", 'INFO');
        }
        
        // 5. Clean old backups
        $logger->log('Cleaning old backups...', 'INFO');
        cleanOldBackups($config, $logger);
        
        // Calculate execution time
        $executionTime = round(microtime(true) - $startTime, 2);
        $logger->log("Backup completed successfully in {$executionTime} seconds", 'INFO');
        
        // Send notification (if enabled)
        if ($config['backup']['email_notifications']) {
            sendNotification($config, "Backup successful at " . date('Y-m-d H:i:s'), $logger);
        }
        
        return true;
        
    } catch (Exception $e) {
        $logger->log("Backup failed: " . $e->getMessage(), 'ERROR');
        
        // Send error notification
        if ($config['backup']['email_notifications']) {
            sendNotification($config, "Backup FAILED: " . $e->getMessage(), $logger, true);
        }
        
        return false;
    }
}

/**
 * Backup Database using mysqldump
 */
function backupDatabase($config, $backupDir, $logger) {
    $db = $config['database'];
    $outputFile = $backupDir . 'database.sql';
    
    // Build mysqldump command
    $mysqldumpPath = $config['mysqldump_path'];
    
    // Check if mysqldump exists
    if (!file_exists($mysqldumpPath)) {
        // Try to find it automatically
        $possiblePaths = [
            '/Applications/MAMP/Library/bin/mysql80/bin/mysqldump',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            'C:/wamp64/bin/mysql/mysql8.0.27/bin/mysqldump.exe',
            'C:/wamp64/bin/mysql/mysql8.0.31/bin/mysqldump.exe',
            'C:/wamp64/bin/mysql/mysql5.7.36/bin/mysqldump.exe',
            'mysqldump'  // System PATH
        ];
        
        $mysqldumpPath = 'mysqldump';  // Default to system PATH
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $mysqldumpPath = $path;
                break;
            }
        }
    }
    
    // Build command
    $command = sprintf(
        '"%s" --user=%s --password=%s --host=%s --port=%s %s > "%s" 2>&1',
        $mysqldumpPath,
        $db['username'],
        $db['password'],
        $db['host'],
        $db['port'],
        $db['database'],
        $outputFile
    );
    
    // Execute mysqldump
    exec($command, $output, $returnCode);
    
    if ($returnCode !== 0 || !file_exists($outputFile) || filesize($outputFile) == 0) {
        throw new Exception("mysqldump failed. Output: " . implode("\n", $output));
    }
    
    $fileSize = formatBytes(filesize($outputFile));
    $logger->log("Database backup size: $fileSize", 'DEBUG');
    
    return $outputFile;
}

/**
 * Backup Uploads Directory
 */
function backupUploads($config, $backupDir, $logger) {
    $uploadsDir = $config['backup']['uploads_dir'];
    $targetDir = $backupDir . 'uploads/';
    
    if (!is_dir($uploadsDir)) {
        $logger->log("Uploads directory not found: $uploadsDir", 'WARNING');
        return null;
    }
    
    // Copy uploads directory
    recursiveCopy($uploadsDir, $targetDir);
    
    $fileSize = formatBytes(getDirSize($targetDir));
    $logger->log("Uploads backup size: $fileSize", 'DEBUG');
    
    return $targetDir;
}

/**
 * Backup Question Images Directory
 */
function backupQuestionImages($config, $backupDir, $logger) {
    $questImagesDir = $config['backup']['quest_images_dir'];
    $targetDir = $backupDir . 'questUploadImages/';
    
    if (!is_dir($questImagesDir)) {
        $logger->log("Question images directory not found: $questImagesDir", 'WARNING');
        return null;
    }
    
    // Copy question images directory
    recursiveCopy($questImagesDir, $targetDir);
    
    $fileSize = formatBytes(getDirSize($targetDir));
    $logger->log("Question images backup size: $fileSize", 'DEBUG');
    
    return $targetDir;
}

/**
 * Backup Configuration Files
 */
function backupConfigFiles($config, $backupDir, $logger) {
    $configDir = $backupDir . 'config/';
    mkdir($configDir, 0755, true);
    
    $baseDir = dirname(__DIR__);
    
    // Files to backup
    $filesToBackup = [
        $baseDir . '/db_connection/dlhs_db_connection.php',
        $baseDir . '/db_connection/optimized_db_connection.php',
        $baseDir . '/db_connection/session_handler.php',
        $baseDir . '/config/optimization.php',
        __DIR__ . '/backup_config.php'
    ];
    
    $backedUpCount = 0;
    foreach ($filesToBackup as $file) {
        if (file_exists($file)) {
            $filename = basename($file);
            copy($file, $configDir . $filename);
            $backedUpCount++;
        }
    }
    
    $logger->log("Backed up $backedUpCount configuration files", 'DEBUG');
    
    return $configDir;
}

/**
 * Compress Backup Directory
 */
function compressBackup($config, $backupDir, $backupName, $logger) {
    $zipFile = $config['backup']['backup_dir'] . $backupName . '.zip';
    
    // Try using 7-Zip if available (faster and better compression)
    if (file_exists($config['7zip_path'])) {
        $command = sprintf(
            '"%s" a -tzip "%s" "%s*" -mx=5',
            $config['7zip_path'],
            $zipFile,
            $backupDir
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($zipFile)) {
            return $zipFile;
        }
    }
    
    // Fallback to PHP's ZipArchive
    $zip = new ZipArchive();
    
    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new Exception("Failed to create zip file: $zipFile");
    }
    
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($backupDir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($files as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($backupDir));
            $zip->addFile($filePath, $relativePath);
        }
    }
    
    $zip->close();
    
    $fileSize = formatBytes(filesize($zipFile));
    $logger->log("Compressed backup size: $fileSize", 'DEBUG');
    
    return $zipFile;
}

/**
 * Clean Old Backups
 */
function cleanOldBackups($config, $logger) {
    $backupDir = $config['backup']['backup_dir'];
    $keepDays = $config['backup']['keep_backups_for_days'];
    $maxBackups = $config['backup']['max_backups'];
    
    $files = glob($backupDir . '*.zip');
    $directories = glob($backupDir . 'dlhs_backup_*', GLOB_ONLYDIR);
    
    $allBackups = array_merge($files, $directories);
    
    // Sort by modification time (oldest first)
    usort($allBackups, function($a, $b) {
        return filemtime($a) - filemtime($b);
    });
    
    $deletedCount = 0;
    $cutoffTime = time() - ($keepDays * 24 * 60 * 60);
    
    // Delete old backups
    foreach ($allBackups as $backup) {
        $shouldDelete = false;
        
        // Delete if older than retention period
        if (filemtime($backup) < $cutoffTime) {
            $shouldDelete = true;
        }
        
        // Delete if exceeds max backups (keep newest)
        if (count($allBackups) - $deletedCount > $maxBackups) {
            $shouldDelete = true;
        }
        
        if ($shouldDelete) {
            if (is_dir($backup)) {
                deleteDirectory($backup);
            } else {
                unlink($backup);
            }
            $deletedCount++;
            $logger->log("Deleted old backup: " . basename($backup), 'DEBUG');
        }
    }
    
    if ($deletedCount > 0) {
        $logger->log("Cleaned up $deletedCount old backup(s)", 'INFO');
    }
}

/**
 * Send Email Notification
 */
function sendNotification($config, $message, $logger, $isError = false) {
    $subject = $isError ? 'DLHS Backup FAILED' : 'DLHS Backup Success';
    $to = $config['backup']['notification_email'];
    
    $headers = [
        'From: DLHS Backup System <noreply@dlhs.com>',
        'Content-Type: text/html; charset=UTF-8'
    ];
    
    $body = "
    <html>
    <body>
        <h2>{$subject}</h2>
        <p>{$message}</p>
        <p><strong>Server:</strong> " . gethostname() . "</p>
        <p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>
    </body>
    </html>
    ";
    
    @mail($to, $subject, $body, implode("\r\n", $headers));
}

/**
 * Helper Functions
 */

function recursiveCopy($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst, 0755, true);
    
    while (($file = readdir($dir)) !== false) {
        if ($file != '.' && $file != '..') {
            if (is_dir($src . '/' . $file)) {
                recursiveCopy($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    
    closedir($dir);
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

function getDirSize($dir) {
    $size = 0;
    
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
        $size += $file->getSize();
    }
    
    return $size;
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Run backup
performBackup($config, $logger);

// Output JSON status for manual runs
if (isset($_GET['manual_run'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Backup completed successfully',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
