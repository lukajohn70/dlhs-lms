<?php
/**
 * Optimized Database Connection Handler
 * Implements connection pooling, persistent connections, and query caching
 * for improved performance under heavy traffic
 */

require_once __DIR__ . '/../config/database.php';

class OptimizedDBConnection {
    private static $instance = null;
    private $connection = null;
    private $queryCache = [];
    private $cacheExpiry = 300; // 5 minutes default
    
    // Database configuration
    private $host = "localhost";
    private $user = "root";
    private $pass = "";
    // Default database name — set to an existing DB on this server if different
    private $db = "deeper_life";
    private $port = 3306;
    private $socket = null;
    private $charset = "utf8mb4";
    private $timezone = "Africa/Lagos";
    private $usePersistent = true;
    private $displayErrors = true;
    
    // Connection pooling settings
    private $maxConnections = 100;
    private $connectionTimeout = 30;
    
    private function __construct() {
        $config = dlhs_database_config();
        $this->host = $config['host'];
        $this->user = $config['user'];
        $this->pass = $config['password'];
        $this->db = $config['database'];
        $this->port = $config['port'];
        $this->socket = $config['socket'] !== '' ? $config['socket'] : null;
        $this->charset = $config['charset'];
        $this->timezone = $config['timezone'];
        $this->displayErrors = $config['display_errors'];
        $this->usePersistent = dlhs_env_bool('DLHS_OPTIMIZED_DB_PERSISTENT', true);
        $this->connect();
    }
    
    /**
     * Singleton pattern to reuse connection
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Create persistent MySQL connection with optimization
     */
    private function connect() {
        ini_set("display_errors", $this->displayErrors ? "1" : "0");
        error_reporting($this->displayErrors ? E_ALL : 0);
        if (function_exists('mysqli_report')) {
            mysqli_report(MYSQLI_REPORT_OFF);
        }

        $connectHost = $this->host;
        if ($this->usePersistent && strpos($connectHost, 'p:') !== 0) {
            $connectHost = 'p:' . $connectHost;
        }

        // Use persistent connection for connection pooling
        $this->connection = @new mysqli(
            $connectHost,
            $this->user,
            $this->pass,
            $this->db,
            $this->port,
            $this->socket
        );

        if ($this->connection->connect_error) {
            $errno = $this->connection->connect_errno;
            error_log("Database connection failed: " . $this->connection->connect_error);

            // Unknown database: try to detect a suitable existing DB on the server
            if ($errno == 1049) {
                $tmpConn = @new mysqli($connectHost, $this->user, $this->pass, '', $this->port, $this->socket);
                if ($tmpConn && !$tmpConn->connect_error) {
                    $res = $tmpConn->query("SHOW DATABASES");
                    if ($res) {
                        $preferred = ['dlhs', 'deeper_life', 'deeper_life_dump', 'deeper_life_backup', 'deeperlife'];
                        $found = null;
                        while ($row = $res->fetch_row()) {
                            $name = $row[0];
                            if (in_array($name, $preferred)) { $found = $name; break; }
                        }

                        if ($found === null) {
                            $res->data_seek(0);
                            while ($row = $res->fetch_row()) {
                                $name = $row[0];
                                if (!in_array($name, ['mysql', 'information_schema', 'performance_schema', 'sys'])) {
                                    $found = $name;
                                    break;
                                }
                            }
                        }

                        if ($found) {
                            error_log("Detected existing database on server: {$found}. Attempting to use it.");
                            $this->db = $found;
                            $this->connection = @new mysqli($connectHost, $this->user, $this->pass, $this->db, $this->port, $this->socket);
                            if ($this->connection && !$this->connection->connect_error) {
                                $tmpConn->close();
                                // success — continue
                            } else {
                                error_log("Failed connecting to discovered DB {$this->db}: " . ($this->connection->connect_error ?? 'unknown'));
                                $tmpConn->close();
                                echo "Database '" . htmlentities($this->db) . "' was detected but connection failed. Check credentials.";
                                exit;
                            }
                        } else {
                            $tmpConn->close();
                            $message = "Database '" . $this->db . "' not found and no suitable existing database detected on the server.\n" .
                                "Please create or import the database (e.g. import deeper_life.sql) or provide the correct database name in configuration.";
                            echo nl2br(htmlentities($message));
                            exit;
                        }
                    } else {
                        $tmpConn->close();
                        echo "Unable to enumerate databases on the server. Check MySQL permissions.";
                        exit;
                    }
                } else {
                    error_log('Temporary server connection failed: ' . ($tmpConn->connect_error ?? 'unknown'));
                    echo "Unable to connect to MySQL server. Check host/username/password and try again.";
                    exit;
                }
            }

            die("Connection failed. Please try again later.");
        }
        
        // Performance optimizations
        $this->connection->set_charset($this->charset);
        
        // Set connection timeout
        $this->connection->options(MYSQLI_OPT_CONNECT_TIMEOUT, $this->connectionTimeout);
        
        // Enable compression for large data transfers (if available)
        if (defined('MYSQLI_OPT_COMPRESS')) {
            $this->connection->options(MYSQLI_OPT_COMPRESS, true);
        }
        
        // Execute optimization queries
        $charset = $this->connection->real_escape_string($this->charset);
        $this->connection->query("SET NAMES '{$charset}'");
        $this->connection->query("SET CHARACTER SET '{$charset}'");
        
        // Enable query cache at session level (MySQL 5.x only, removed in MySQL 8.0+)
        // Check MySQL version first
        $version = $this->connection->server_version;
        if ($version < 80000) { // MySQL version < 8.0.0
            @$this->connection->query("SET SESSION query_cache_type = ON");
        }
        
        // Set timezone
        date_default_timezone_set($this->timezone);
    }
    
    /**
     * Get the database connection
     */
    public function getConnection() {
        // Check if connection is still alive
        if (!$this->connection->ping()) {
            $this->connect();
        }
        return $this->connection;
    }
    
    /**
     * Execute cached query - reduces database load
     * @param string $query - SQL query
     * @param string $cacheKey - Unique cache identifier
     * @param int $cacheDuration - Cache duration in seconds
     * @return mixed - Query result or cached result
     */
    public function cachedQuery($query, $cacheKey = null, $cacheDuration = null) {
        $cacheDuration = $cacheDuration ?? $this->cacheExpiry;
        $cacheKey = $cacheKey ?? md5($query);
        
        // Check cache
        if (isset($this->queryCache[$cacheKey])) {
            $cached = $this->queryCache[$cacheKey];
            if (time() - $cached['time'] < $cacheDuration) {
                return $cached['result'];
            }
        }
        
        // Execute query
        $result = $this->connection->query($query);
        
        if ($result === false) {
            error_log("Query failed: " . $this->connection->error . " | Query: " . $query);
            return false;
        }
        
        // Cache result for SELECT queries only
        if ($result instanceof mysqli_result) {
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            
            $this->queryCache[$cacheKey] = [
                'result' => $data,
                'time' => time()
            ];
            
            return $data;
        }
        
        return $result;
    }
    
    /**
     * Clear specific cache or all cache
     */
    public function clearCache($cacheKey = null) {
        if ($cacheKey === null) {
            $this->queryCache = [];
        } else {
            unset($this->queryCache[$cacheKey]);
        }
    }
    
    /**
     * Prepare statement with caching
     */
    public function prepare($query) {
        return $this->connection->prepare($query);
    }
    
    /**
     * Execute query directly
     */
    public function query($query) {
        return $this->connection->query($query);
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->connection->insert_id;
    }
    
    /**
     * Get affected rows
     */
    public function affectedRows() {
        return $this->connection->affected_rows;
    }
    
    /**
     * Escape string
     */
    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }
    
    /**
     * Get connection error
     */
    public function getError() {
        return $this->connection->error;
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->begin_transaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollback();
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Backward compatibility - create global $connection variable
$dbInstance = OptimizedDBConnection::getInstance();
$connection = $dbInstance->getConnection();

?>
