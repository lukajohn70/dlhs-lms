<?php
/**
 * DLHS Automated Backup Configuration
 * 
 * This configuration file contains all settings for the automated backup system.
 * Adjust these settings according to your environment.
 */

$dlhsConfigPath = dirname(__DIR__) . '/config/database.php';
if (is_readable($dlhsConfigPath)) {
    require_once $dlhsConfigPath;
    $dlhsDbConfig = dlhs_database_config();
} else {
    $dlhsDbConfig = [
        'host' => 'localhost',
        'user' => 'root',
        'password' => '',
        'database' => 'deeper_life',
        'port' => 3306,
    ];
}

return [
    // Database Configuration
    'database' => [
        'host' => $dlhsDbConfig['host'],
        'username' => $dlhsDbConfig['user'],
        'password' => $dlhsDbConfig['password'],
        'database' => $dlhsDbConfig['database'],
        'port' => (string) $dlhsDbConfig['port']
    ],
    
    // Backup Settings
    'backup' => [
        // Where to store backups (absolute path)
        'backup_dir' => __DIR__ . '/backups/',
        
        // Backup retention settings
        'keep_backups_for_days' => 7,  // Keep backups for 7 days
        'max_backups' => 100,  // Maximum number of backups to keep
        
        // Backup intervals (in minutes)
        'interval_minutes' => 30,
        
        // Compress backups? (saves space)
        'compress' => true,
        
        // Include file uploads in backup?
        'backup_uploads' => true,
        
        // Uploads directory
        'uploads_dir' => dirname(__DIR__) . '/uploads/',
        
        // Question images directory
        'quest_images_dir' => dirname(__DIR__) . '/questUploadImages/',
        
        // Send email notifications?
        'email_notifications' => false,
        'notification_email' => 'admin@school.com'
    ],
    
    // MySQL dump path (where mysqldump.exe is located)
    'mysqldump_path' => function_exists('dlhs_env_first') ? dlhs_env_first([
        'DLHS_MYSQLDUMP_PATH',
        'MYSQLDUMP_PATH',
    ], 'mysqldump') : 'mysqldump',
    
    // 7-Zip path for compression (if installed)
    '7zip_path' => function_exists('dlhs_env_first') ? dlhs_env_first([
        'DLHS_7ZIP_PATH',
        'SEVENZIP_PATH',
    ], '7z') : '7z',
    
    // Logging
    'logging' => [
        'enabled' => true,
        'log_file' => __DIR__ . '/logs/backup.log',
        'log_level' => 'INFO'  // DEBUG, INFO, WARNING, ERROR
    ],
    
    // Cloud backup settings (optional - for future use)
    'cloud_backup' => [
        'enabled' => false,
        'provider' => 'dropbox',  // dropbox, google_drive, onedrive
        'api_key' => ''
    ]
];
?>
