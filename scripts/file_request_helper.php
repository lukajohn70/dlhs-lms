<?php
if (!function_exists('dlhsEnsureFileRequestTablesExist')) {
    function dlhsEnsureFileRequestTablesExist(mysqli $connection)
    {
        // 1. Create file_requests table (with yearGroupId from the start)
        $connection->query("CREATE TABLE IF NOT EXISTS `file_requests` (
          `requestId` int NOT NULL AUTO_INCREMENT,
          `teacherId` int NOT NULL,
          `title` varchar(255) NOT NULL,
          `instructions` text,
          `classId` int DEFAULT NULL,
          `yearGroupId` int DEFAULT NULL,
          `subjectId` int DEFAULT NULL,
          `dueDate` datetime DEFAULT NULL,
          `allowedTypes` varchar(255) DEFAULT 'pdf,doc,docx,jpg,png,zip',
          `maxFileSizeMB` int DEFAULT 10,
          `isActive` tinyint(1) DEFAULT '1',
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`requestId`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Migration: add yearGroupId column to existing installations that don't have it yet
        $colCheck = $connection->query("SHOW COLUMNS FROM `file_requests` LIKE 'yearGroupId'");
        if ($colCheck && $colCheck->num_rows === 0) {
            $connection->query("ALTER TABLE `file_requests` ADD COLUMN `yearGroupId` int DEFAULT NULL AFTER `classId`");
        }

        // 2. Create file_request_submissions table
        $connection->query("CREATE TABLE IF NOT EXISTS `file_request_submissions` (
          `submissionId` int NOT NULL AUTO_INCREMENT,
          `requestId` int NOT NULL,
          `studentId` int NOT NULL,
          `fileName` varchar(255) NOT NULL,
          `originalName` varchar(255) NOT NULL,
          `filePath` varchar(500) NOT NULL,
          `fileSize` bigint NOT NULL,
          `submittedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          `isLate` tinyint(1) DEFAULT '0',
          PRIMARY KEY (`submissionId`),
          UNIQUE KEY `uniq_request_student` (`requestId`, `studentId`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        return true;
    }
}
?>
