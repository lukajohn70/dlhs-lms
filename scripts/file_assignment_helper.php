<?php
if (!function_exists('dlhsTableExists')) {
    function dlhsTableExists(mysqli $connection, $tableName)
    {
        $tableName = preg_replace('/[^A-Za-z0-9_]/', '', (string) $tableName);
        if ($tableName === '') {
            return false;
        }

        $result = $connection->query("SHOW TABLES LIKE '{$tableName}'");
        return $result && $result->num_rows > 0;
    }
}

if (!function_exists('dlhsEnsureFileStudentAssignmentsTable')) {
    function dlhsEnsureFileStudentAssignmentsTable(mysqli $connection)
    {
        return dlhsEnsureAllFileManagementTablesExist($connection);
    }
}

if (!function_exists('dlhsEnsureAllFileManagementTablesExist')) {
    function dlhsEnsureAllFileManagementTablesExist(mysqli $connection)
    {
        // 1. Create file_categories table
        $connection->query("CREATE TABLE IF NOT EXISTS `file_categories` (
          `categoryId` int NOT NULL AUTO_INCREMENT,
          `categoryName` varchar(100) NOT NULL,
          `description` text,
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`categoryId`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Populate default file categories
        $connection->query("INSERT IGNORE INTO `file_categories` (categoryId, categoryName, description) VALUES
            (1, 'Assignments', 'Student assignments and coursework'),
            (2, 'Class Notes', 'Lecture notes and study guides'),
            (3, 'Slides', 'Presentation slides and visual materials'),
            (4, 'Multimedia', 'Videos, audio clips, and educational animations'),
            (5, 'Resources', 'General reference documents and worksheets'),
            (6, 'Exams', 'Mock papers and exam prep files'),
            (7, 'Syllabus', 'Course syllabus and learning plans'),
            (8, 'Lesson Plans', 'Weekly and termly lesson plans and schemes of work'),
            (9, 'Administrative', 'Circulars, memos, policies, and official reports'),
            (10, 'Departmental', 'Subject meetings, syllabi, and departmental guides'),
            (11, 'Assessments & Marksheets', 'Score sheets, grading guides, and assessment drafts')");

        // 2. Create file_uploads table
        $connection->query("CREATE TABLE IF NOT EXISTS `file_uploads` (
          `fileId` int NOT NULL AUTO_INCREMENT,
          `fileName` varchar(255) NOT NULL,
          `originalName` varchar(255) NOT NULL,
          `filePath` varchar(500) NOT NULL,
          `fileSize` bigint NOT NULL,
          `fileType` varchar(100) NOT NULL,
          `categoryId` int NOT NULL,
          `subjectId` int NOT NULL,
          `uploadedBy` int NOT NULL,
          `uploadedFor` varchar(20) NOT NULL,
          `targetClassId` int DEFAULT NULL,
          `targetStudentId` int DEFAULT NULL,
          `title` varchar(255) NOT NULL,
          `description` text,
          `isAssignment` tinyint(1) DEFAULT '0',
          `dueDate` datetime DEFAULT NULL,
          `maxMarks` int DEFAULT NULL,
          `instructions` text,
          `isActive` tinyint(1) DEFAULT '1',
          `downloadCount` int DEFAULT '0',
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`fileId`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // 3. Create assignment_submissions table
        $connection->query("CREATE TABLE IF NOT EXISTS `assignment_submissions` (
          `submissionId` int NOT NULL AUTO_INCREMENT,
          `fileId` int NOT NULL,
          `studentId` int NOT NULL,
          `submissionFile` varchar(500) NOT NULL,
          `submissionText` text,
          `submittedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          `isLate` tinyint(1) DEFAULT '0',
          `status` enum('submitted','graded','returned') DEFAULT 'submitted',
          `grade` decimal(5,2) DEFAULT NULL,
          `feedback` text,
          `gradedBy` int DEFAULT NULL,
          `gradedAt` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`submissionId`),
          KEY `idx_submissions_student` (`studentId`),
          KEY `idx_submissions_file` (`fileId`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // 4. Create class_assignments table
        $connection->query("CREATE TABLE IF NOT EXISTS `class_assignments` (
          `classAssignmentId` int NOT NULL AUTO_INCREMENT,
          `fileId` int NOT NULL,
          `classId` int NOT NULL,
          `dueDate` datetime DEFAULT NULL,
          `assignedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`classAssignmentId`),
          KEY `idx_class_assignments_file` (`fileId`),
          KEY `idx_class_assignments_class` (`classId`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // 5. Create file_student_assignments table
        $connection->query("CREATE TABLE IF NOT EXISTS `file_student_assignments` (
          `assignmentId` INT NOT NULL AUTO_INCREMENT,
          `fileId` INT NOT NULL,
          `studentId` INT NOT NULL,
          `assignedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`assignmentId`),
          UNIQUE KEY `uniq_file_student_assignment` (`fileId`, `studentId`),
          KEY `idx_file_student_assignments_file` (`fileId`),
          KEY `idx_file_student_assignments_student` (`studentId`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // 6. Create file_access_logs table
        $connection->query("CREATE TABLE IF NOT EXISTS `file_access_logs` (
          `logId` int NOT NULL AUTO_INCREMENT,
          `fileId` int NOT NULL,
          `userId` int NOT NULL,
          `userType` varchar(20) NOT NULL,
          `action` varchar(50) NOT NULL,
          `ipAddress` varchar(45) DEFAULT NULL,
          `userAgent` varchar(255) DEFAULT NULL,
          `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`logId`),
          KEY `idx_access_logs_file` (`fileId`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // 7. Create file_staff_assignments table for teacher-to-teacher and admin-to-teacher targeted file sharing
        $connection->query("CREATE TABLE IF NOT EXISTS `file_staff_assignments` (
          `assignmentId` INT NOT NULL AUTO_INCREMENT,
          `fileId` INT NOT NULL,
          `staffId` INT NOT NULL,
          `assignedAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`assignmentId`),
          UNIQUE KEY `uniq_file_staff` (`fileId`, `staffId`),
          KEY `idx_file_staff_file` (`fileId`),
          KEY `idx_file_staff_staff` (`staffId`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Safely add columns to file_uploads for teacher & admin file sharing
        $columnsToEnsure = [
            'isPublishedToStudents' => "ALTER TABLE `file_uploads` ADD COLUMN `isPublishedToStudents` TINYINT(1) NOT NULL DEFAULT 0",
            'uploaderType'          => "ALTER TABLE `file_uploads` ADD COLUMN `uploaderType` VARCHAR(20) NOT NULL DEFAULT 'staff'",
            'targetScope'           => "ALTER TABLE `file_uploads` ADD COLUMN `targetScope` VARCHAR(30) NOT NULL DEFAULT 'students'",
            'targetStaffId'         => "ALTER TABLE `file_uploads` ADD COLUMN `targetStaffId` INT DEFAULT NULL",
            'adminStatus'           => "ALTER TABLE `file_uploads` ADD COLUMN `adminStatus` VARCHAR(20) NOT NULL DEFAULT 'pending'",
            'adminNotes'            => "ALTER TABLE `file_uploads` ADD COLUMN `adminNotes` TEXT NULL",
            'adminReviewedAt'       => "ALTER TABLE `file_uploads` ADD COLUMN `adminReviewedAt` DATETIME NULL",
            'adminReviewedBy'       => "ALTER TABLE `file_uploads` ADD COLUMN `adminReviewedBy` INT NULL"
        ];

        foreach ($columnsToEnsure as $colName => $alterSql) {
            $colCheck = $connection->query("SHOW COLUMNS FROM `file_uploads` LIKE '{$colName}'");
            if ($colCheck && $colCheck->num_rows === 0) {
                @$connection->query($alterSql);
            }
        }

        // Ensure uploadedFor is at least VARCHAR(50)
        @$connection->query("ALTER TABLE `file_uploads` MODIFY COLUMN `uploadedFor` VARCHAR(50) NOT NULL");
        // Ensure subjectId can default to 0 for administrative / general shares
        @$connection->query("ALTER TABLE `file_uploads` MODIFY COLUMN `subjectId` INT DEFAULT 0");

        return true;
    }
}
?>
