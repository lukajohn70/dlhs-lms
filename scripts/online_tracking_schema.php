<?php
/**
 * Online tracking schema validation
 */

function dlhsEnsureOnlineTrackingTables($connection) {
    // Check if table exists
    $tableExists = $connection->query("SHOW TABLES LIKE 'online_users'");
    
    if ($tableExists && $tableExists->num_rows > 0) {
        return true;
    }

    $sql = "CREATE TABLE IF NOT EXISTS `online_users` (
        `id` int NOT NULL AUTO_INCREMENT,
        `userId` int NOT NULL,
        `userType` enum('student','staff','admin') NOT NULL,
        `userName` varchar(100) DEFAULT NULL,
        `fullName` varchar(255) DEFAULT NULL,
        `currentPage` varchar(255) DEFAULT NULL,
        `ipAddress` varchar(45) DEFAULT NULL,
        `lastActivity` datetime NOT NULL,
        `sessionId` varchar(100) NOT NULL,
        `isActive` tinyint(1) DEFAULT '1',
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_session` (`userId`,`userType`,`sessionId`),
        KEY `idx_user` (`userId`,`userType`),
        KEY `idx_activity` (`lastActivity`),
        KEY `idx_active` (`isActive`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;";
    
    return $connection->query($sql);
}
?>
