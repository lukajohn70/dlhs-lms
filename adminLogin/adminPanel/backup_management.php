<?php
session_start();
require_once 'userExpiredSession.php';
require_once '../../db_connection/optimized_db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['adminId'])) {
    header("Location: ../index.php");
    exit();
}

$adminId = $_SESSION['adminId'];

// Load backup configuration
$backupConfig = require_once '../../backup/backup_config.php';

// Get list of existing backups
function getBackups($backupDir) {
    $backups = [];
    
    // Get zip files
    $files = glob($backupDir . '*.zip');
    
    // Get directories
    $dirs = glob($backupDir . 'dlhs_backup_*', GLOB_ONLYDIR);
    
    $allBackups = array_merge($files, $dirs);
    
    foreach ($allBackups as $backup) {
        $backups[] = [
            'name' => basename($backup),
            'path' => $backup,
            'size' => is_dir($backup) ? getDirSize($backup) : filesize($backup),
            'date' => filemtime($backup),
            'type' => is_dir($backup) ? 'directory' : 'zip'
        ];
    }
    
    // Sort by date (newest first)
    usort($backups, function($a, $b) {
        return $b['date'] - $a['date'];
    });
    
    return $backups;
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

$backups = getBackups($backupConfig['backup']['backup_dir']);
$totalBackups = count($backups);
$totalSize = array_sum(array_column($backups, 'size'));

// Calculate backup statistics
$backupStats = [
    'total_backups' => $totalBackups,
    'total_size' => formatBytes($totalSize),
    'oldest_backup' => $totalBackups > 0 ? date('Y-m-d H:i:s', min(array_column($backups, 'date'))) : 'N/A',
    'newest_backup' => $totalBackups > 0 ? date('Y-m-d H:i:s', max(array_column($backups, 'date'))) : 'N/A',
    'avg_size' => $totalBackups > 0 ? formatBytes($totalSize / $totalBackups) : 'N/A'
];

// Read backup log (last 100 lines)
$logFile = dirname(__DIR__, 2) . '/backup/logs/backup.log';
$logLines = [];
if (file_exists($logFile)) {
    $logLines = array_slice(file($logFile), -100);
    $logLines = array_reverse($logLines);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Backup Management - DLHS Admin">
    <link rel="shortcut icon" href="../images/dlhslogo3.jpg">
    
    <title>Backup Management | DLHS Admin</title>
    
    <!-- Bootstrap CSS -->    
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <link href="css/elegant-icons-style.css" rel="stylesheet" />
    <link href="css/font-awesome.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />
    <link href="sideBar_style.css" rel="stylesheet" />
    <style>
        
        .stats-grid {
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-left: 4px solid #667eea;
            margin-bottom: 20px;
        }
        
        .stat-card .stat-label {
            color: #6c757d;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .stat-card .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #2d3748;
        }
        
        .action-buttons {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .btn-backup {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 6px;
            transition: transform 0.2s;
        }
        
        .btn-backup:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }
        
        .backups-table {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .backups-table h3 {
            margin-bottom: 20px;
            color: #2d3748;
            font-size: 18px;
            font-weight: 600;
        }
        
        .backup-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            margin-bottom: 10px;
            transition: all 0.2s;
        }
        
        .backup-item:hover {
            background: #f7fafc;
            border-color: #667eea;
        }
        
        .backup-info {
            flex: 1;
        }
        
        .backup-name {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
        }
        
        .backup-meta {
            font-size: 12px;
            color: #718096;
        }
        
        .backup-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .log-viewer {
            background: #1a202c;
            color: #a0aec0;
            padding: 20px;
            border-radius: 10px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 400px;
            overflow-y: auto;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .log-viewer h3 {
            color: white;
            margin-bottom: 15px;
            font-family: 'Segoe UI', sans-serif;
        }
        
        .log-line {
            margin-bottom: 5px;
            line-height: 1.6;
        }
        
        .log-line.error {
            color: #fc8181;
        }
        
        .log-line.warning {
            color: #f6ad55;
        }
        
        .log-line.success {
            color: #68d391;
        }
        
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        
        .loading-overlay.active {
            display: flex;
        }
        
        .loading-content {
            background: white;
            padding: 40px;
            border-radius: 10px;
            text-align: center;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    <style>
        /* DLHS theme overrides to ensure theme variables take effect on this page */
        :root { }

        /* Stat cards accent */
        .stat-card {
            border-left-color: var(--dlhs-primary) !important;
        }

        .stat-card .stat-label { color: var(--dlhs-muted) !important; }
        .stat-card .stat-value { color: var(--dlhs-text) !important; }

        /* Primary action buttons */
        .action-buttons .btn-dlhs {
            background: linear-gradient(135deg, var(--dlhs-primary) 0%, var(--dlhs-primary-dark) 100%) !important;
            color: #fff !important;
            border: 0 !important;
        }

        /* Backup item hover accent */
        .backup-item:hover { border-color: var(--dlhs-primary) !important; }

        /* Badges */
        .badge { background: var(--dlhs-primary) !important; color: #fff !important; }

        /* Log viewer subtle tweak */
        .log-viewer { background: #0f1720 !important; color: #a0aec0 !important; }
    </style>
</head>
<body>
  <!-- container section start -->
  <section id="container" class="">
    
    <!--Including the header-->
    <?php include 'header.php'; ?>
    
    <!--Including the sidebar-->
    <?php include 'sideBar.php'; ?>
           
    <!--main content start-->
    <section id="main-content">
        <section class="wrapper">            
            <!--overview start-->
            <div class="row">
                <div class="col-lg-12">
                    <h3 class="page-header"><i class="fa fa-database"></i> Backup Management</h3>
                    <ol class="breadcrumb">
                        <li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
                        <li><i class="fa fa-cogs"></i><a href="#">Settings</a></li>
                        <li><i class="fa fa-database"></i>Backup Management</li>
                    </ol>
                </div>
            </div>
        
            <!-- Statistics -->
            <div class="row stats-grid">
                <div class="col-md-3">
                    <div class="stat-card dlhs-card">
                        <div class="stat-label">Total Backups</div>
                        <div class="stat-value"><?php echo $backupStats['total_backups']; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card dlhs-card">
                        <div class="stat-label">Total Size</div>
                        <div class="stat-value"><?php echo $backupStats['total_size']; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card dlhs-card">
                        <div class="stat-label">Average Size</div>
                        <div class="stat-value"><?php echo $backupStats['avg_size']; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card dlhs-card">
                        <div class="stat-label">Newest Backup</div>
                        <div class="stat-value" style="font-size: 14px;"><?php echo $backupStats['newest_backup']; ?></div>
                    </div>
                </div>
            </div>
        
            <!-- Action Buttons -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="action-buttons dlhs-card">
            <button class="btn btn-dlhs" onclick="runManualBackup()">
                <i class="fa fa-play-circle"></i> Run Backup Now
            </button>
            <button class="btn btn-dlhs" onclick="refreshPage()">
                <i class="fa fa-refresh"></i> Refresh
            </button>
            <a href="../../backup/logs/backup.log" target="_blank" class="btn btn-dlhs">
                <i class="fa fa-file-text"></i> View Full Log
            </a>
                    </div>
                </div>
            </div>
        
            <!-- Backups List -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="backups-table dlhs-card">
            <h3><i class="fa fa-archive"></i> Available Backups (<?php echo $totalBackups; ?>)</h3>
            
            <?php if (empty($backups)): ?>
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> No backups found. Click "Run Backup Now" to create your first backup.
                </div>
            <?php else: ?>
                <?php foreach ($backups as $backup): ?>
                    <div class="backup-item">
                        <div class="backup-info">
                            <div class="backup-name">
                                <i class="fa fa-<?php echo $backup['type'] == 'zip' ? 'file-archive-o' : 'folder'; ?>"></i>
                                <?php echo htmlspecialchars($backup['name']); ?>
                            </div>
                            <div class="backup-meta">
                                <?php echo formatBytes($backup['size']); ?> • 
                                <?php echo date('Y-m-d H:i:s', $backup['date']); ?> • 
                                <?php echo $backup['type']; ?>
                            </div>
                        </div>
                        <div class="backup-actions">
                            <a href="download_backup.php?file=<?php echo urlencode($backup['name']); ?>" 
                               class="btn btn-success btn-sm">
                                <i class="fa fa-download"></i> Download
                            </a>
                            <button class="btn btn-danger btn-sm" 
                                    onclick="deleteBackup('<?php echo htmlspecialchars($backup['name']); ?>')">
                                <i class="fa fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
                    </div>
                </div>
            </div>
        
            <!-- Log Viewer -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="log-viewer dlhs-card">
            <h3><i class="fa fa-terminal"></i> Recent Backup Log (Last 100 Lines)</h3>
            <?php if (empty($logLines)): ?>
                <div style="color: #718096;">No log entries found.</div>
            <?php else: ?>
                <?php foreach ($logLines as $line): ?>
                    <?php
                    $class = '';
                    if (strpos($line, 'ERROR') !== false) $class = 'error';
                    elseif (strpos($line, 'WARNING') !== false) $class = 'warning';
                    elseif (strpos($line, 'completed successfully') !== false) $class = 'success';
                    ?>
                    <div class="log-line <?php echo $class; ?>"><?php echo htmlspecialchars($line); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
                    </div>
                </div>
            </div>
            
        </section>
    </section>
    <!--main content end-->
    
    <!-- footer -->
    <div class="text-right">
        <div class="credits">
            <?php include "footer.php"; ?>
        </div>
    </div>
  </section>
  <!-- container section end -->
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <h3>Running Backup...</h3>
            <p>Please wait while the backup is being created.</p>
        </div>
    </div>
    
  <!-- javascripts -->
  <script src="js/jquery.js"></script>
  <script src="js/bootstrap.min.js"></script>
  <!-- nice scroll -->
  <script src="js/jquery.scrollTo.min.js"></script>
  <script src="js/jquery.nicescroll.js"></script>
  <!--custome script for all page-->
  <script src="js/scripts.js"></script>
    
  <script>
        function runManualBackup() {
            if (!confirm('Run a manual backup now? This may take a few minutes.')) {
                return;
            }
            
            document.getElementById('loadingOverlay').classList.add('active');
            
            fetch('../../backup/automated_backup.php?manual_run=1')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loadingOverlay').classList.remove('active');
                    
                    if (data.success) {
                        alert('Backup completed successfully!');
                        location.reload();
                    } else {
                        alert('Backup failed: ' + data.message);
                    }
                })
                .catch(error => {
                    document.getElementById('loadingOverlay').classList.remove('active');
                    alert('Error running backup: ' + error);
                });
        }
        
        function deleteBackup(filename) {
            if (!confirm('Are you sure you want to delete this backup?\n\n' + filename)) {
                return;
            }
            
            fetch('delete_backup.php?file=' + encodeURIComponent(filename), {
                method: 'POST'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Backup deleted successfully!');
                        location.reload();
                    } else {
                        alert('Failed to delete backup: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error deleting backup: ' + error);
                });
        }
        
        function refreshPage() {
            location.reload();
        }
    </script>
</body>
</html>

