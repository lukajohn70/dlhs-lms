<?php
/**
 * Database Session Handler
 * Replaces file-based sessions for better performance under heavy traffic
 * Prevents session file locking issues with concurrent users
 */

class DatabaseSessionHandler implements SessionHandlerInterface {
    private $connection;
    private $tableName = 'sessions';
    private $lifetime;
    
    public function __construct($connection) {
        $this->connection = $connection;
        $this->lifetime = get_cfg_var('session.gc_maxlifetime') ?: 1440;
        
        // Create sessions table if it doesn't exist
        $this->createSessionTable();
    }
    
    /**
     * Create sessions table
     */
    private function createSessionTable() {
        $query = "CREATE TABLE IF NOT EXISTS `{$this->tableName}` (
            `session_id` VARCHAR(255) NOT NULL PRIMARY KEY,
            `session_data` MEDIUMTEXT NOT NULL,
            `last_activity` INT(10) UNSIGNED NOT NULL,
            `user_id` INT NULL,
            `user_type` VARCHAR(20) NULL,
            `ip_address` VARCHAR(45) NULL,
            INDEX `idx_last_activity` (`last_activity`),
            INDEX `idx_user` (`user_id`, `user_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->connection->query($query);
    }
    
    /**
     * Open session
     */
    public function open($savePath, $sessionName): bool {
        return true;
    }
    
    /**
     * Close session
     */
    public function close(): bool {
        // Clean up expired sessions occasionally (1% chance)
        if (rand(1, 100) === 1) {
            $this->gc($this->lifetime);
        }
        return true;
    }
    
    /**
     * Read session data
     */
    public function read($sessionId): string {
        $stmt = $this->connection->prepare(
            "SELECT session_data FROM {$this->tableName} WHERE session_id = ? AND last_activity >= ?"
        );
        
        if (!$stmt) {
            error_log("Session read prepare failed: " . $this->connection->error);
            return '';
        }
        
        $minActivity = time() - $this->lifetime;
        $stmt->bind_param('si', $sessionId, $minActivity);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row['session_data'];
        }
        
        return '';
    }
    
    /**
     * Write session data
     */
    public function write($sessionId, $sessionData): bool {
        $userId = null;
        $userType = null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        
        // Extract user info from session data if available
        if (isset($_SESSION['studentId'])) {
            $userId = $_SESSION['studentId'];
            $userType = 'student';
        } elseif (isset($_SESSION['staffId'])) {
            $userId = $_SESSION['staffId'];
            $userType = 'staff';
        } elseif (isset($_SESSION['adminId'])) {
            $userId = $_SESSION['adminId'];
            $userType = 'admin';
        }
        
        $stmt = $this->connection->prepare(
            "INSERT INTO {$this->tableName} (session_id, session_data, last_activity, user_id, user_type, ip_address) 
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE 
             session_data = VALUES(session_data),
             last_activity = VALUES(last_activity),
             user_id = VALUES(user_id),
             user_type = VALUES(user_type)"
        );
        
        if (!$stmt) {
            error_log("Session write prepare failed: " . $this->connection->error);
            return false;
        }
        
        $timestamp = time();
        $stmt->bind_param('ssiiss', $sessionId, $sessionData, $timestamp, $userId, $userType, $ipAddress);
        
        return $stmt->execute();
    }
    
    /**
     * Destroy session
     */
    public function destroy($sessionId): bool {
        $stmt = $this->connection->prepare("DELETE FROM {$this->tableName} WHERE session_id = ?");
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param('s', $sessionId);
        return $stmt->execute();
    }
    
    /**
     * Garbage collection - remove expired sessions
     */
    public function gc($maxLifetime): int|false {
        $expiry = time() - $maxLifetime;
        $stmt = $this->connection->prepare("DELETE FROM {$this->tableName} WHERE last_activity < ?");
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param('i', $expiry);
        $stmt->execute();
        
        return $stmt->affected_rows;
    }
    
    /**
     * Get active sessions count
     */
    public function getActiveSessionsCount($userType = null) {
        if ($userType) {
            $stmt = $this->connection->prepare(
                "SELECT COUNT(*) as count FROM {$this->tableName} 
                 WHERE last_activity >= ? AND user_type = ?"
            );
            $minActivity = time() - $this->lifetime;
            $stmt->bind_param('is', $minActivity, $userType);
        } else {
            $stmt = $this->connection->prepare(
                "SELECT COUNT(*) as count FROM {$this->tableName} WHERE last_activity >= ?"
            );
            $minActivity = time() - $this->lifetime;
            $stmt->bind_param('i', $minActivity);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['count'] ?? 0;
    }
}

// Initialize database session handler
if (!isset($connection)) {
    require_once __DIR__ . '/optimized_db_connection.php';
}

$sessionHandler = new DatabaseSessionHandler($connection);
session_set_save_handler($sessionHandler, true);

// Configure session for better performance
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);
ini_set('session.gc_maxlifetime', 1440);
ini_set('session.cookie_lifetime', 0);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

// Use cookies only (not URL rewriting)
ini_set('session.use_only_cookies', 1);
ini_set('session.use_trans_sid', 0);

?>




