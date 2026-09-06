<?php
/**
 * User Presence Tracking Script
 * Include this at the top of every page to track online users
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_connection/dlhs_db_connection.php';
require_once __DIR__ . '/scripts/online_tracking_schema.php';

function trackUserPresence($connection) {
    if (!dlhsEnsureOnlineTrackingTables($connection)) {
        return false;
    }

    // Determine user type and ID
    $userId = null;
    $userType = null;
    $userName = null;
    $fullName = null;
    
    if (isset($_SESSION['studentId'])) {
        $userId = $_SESSION['studentId'];
        $userType = 'student';
        
        // Get student details
        $stmt = $connection->prepare("SELECT CONCAT(firstName, ' ', surname) as fullName FROM studentlogin WHERE studentId = ?");
        if (!$stmt) {
            error_log('Student presence lookup prepare failed: ' . $connection->error);
            return false;
        }
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $fullName = $row['fullName'];
        }
        $stmt->close();
        
    } elseif (isset($_SESSION['staffId'])) {
        $userId = $_SESSION['staffId'];
        $userType = 'staff';
        
        // Get staff details
        $stmt = $connection->prepare("SELECT CONCAT(firstName, ' ', surname) as fullName FROM stafflogin WHERE staffId = ?");
        if (!$stmt) {
            error_log('Staff presence lookup prepare failed: ' . $connection->error);
            return false;
        }
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $fullName = $row['fullName'];
        }
        $stmt->close();
        
    } elseif (isset($_SESSION['adminId'])) {
        $userId = $_SESSION['adminId'];
        $userType = 'admin';
        $userName = $_SESSION['adminEmail'] ?? '';
        
        // Use session data for admin name
        $fullName = $_SESSION['adminEmail'] ?? 'Administrator';
    }
    
    if ($userId && $userType) {
        $sessionId = session_id();
        $currentPage = basename($_SERVER['PHP_SELF']);
        
        // Get accurate IP address (check for proxy headers)
        $ipAddress = 'unknown';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            // IP from shared internet
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // IP passed from proxy
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ipAddress = $_SERVER['REMOTE_ADDR'];
        }
        
        // Clean IP address (take first IP if multiple)
        $ipAddress = explode(',', $ipAddress)[0];
        $ipAddress = trim($ipAddress);
        
        $lastActivity = date('Y-m-d H:i:s');
        
        // Insert or update user presence
        $stmt = $connection->prepare("
            INSERT INTO online_users (userId, userType, userName, fullName, currentPage, ipAddress, lastActivity, sessionId, isActive)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
                currentPage = VALUES(currentPage),
                ipAddress = VALUES(ipAddress),
                lastActivity = VALUES(lastActivity),
                isActive = 1
        ");

        if (!$stmt) {
            error_log('Online user presence prepare failed: ' . $connection->error);
            return false;
        }
        
        $stmt->bind_param("isssssss", $userId, $userType, $userName, $fullName, $currentPage, $ipAddress, $lastActivity, $sessionId);
        $stmt->execute();
        $stmt->close();
        
        // Clean up inactive users (no activity in last 5 minutes)
        $cleanupTime = date('Y-m-d H:i:s', strtotime('-5 minutes'));
        $connection->query("UPDATE online_users SET isActive = 0 WHERE lastActivity < '$cleanupTime'");
        
        // Delete very old inactive records (older than 24 hours)
        $deleteTime = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $connection->query("DELETE FROM online_users WHERE isActive = 0 AND lastActivity < '$deleteTime'");
    }

    return true;
}

// Auto-track user presence
trackUserPresence($connection);
?>
