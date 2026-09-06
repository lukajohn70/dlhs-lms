<?php
/**
 * Performance Monitoring and Optimization Utility
 * Tracks page load times, memory usage, and query performance
 * Helps identify bottlenecks under heavy traffic
 */

require_once __DIR__ . '/../config/env.php';

class PerformanceMonitor {
    private static $instance = null;
    private $startTime;
    private $startMemory;
    private $queryCount = 0;
    private $queries = [];
    private $connection;
    private $enabled = true;
    private $logToDatabase = false;
    
    private function __construct() {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage();
        
        // Enable performance monitoring in development/staging
        $this->enabled = (dlhs_env_first(array('DLHS_APP_ENV', 'APP_ENV'), 'local') !== 'production');
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Set database connection for logging
     */
    public function setConnection($connection) {
        $this->connection = $connection;
        $this->logToDatabase = true;
    }
    
    /**
     * Track a database query
     */
    public function trackQuery($query, $executionTime = null) {
        if (!$this->enabled) return;
        
        $this->queryCount++;
        
        if ($executionTime !== null) {
            $this->queries[] = [
                'query' => $query,
                'time' => $executionTime,
                'timestamp' => microtime(true)
            ];
        }
    }
    
    /**
     * Get execution statistics
     */
    public function getStats() {
        $executionTime = microtime(true) - $this->startTime;
        $memoryUsed = memory_get_usage() - $this->startMemory;
        $peakMemory = memory_get_peak_usage();
        
        return [
            'execution_time' => round($executionTime, 4),
            'execution_time_ms' => round($executionTime * 1000, 2),
            'memory_used' => $this->formatBytes($memoryUsed),
            'memory_used_bytes' => $memoryUsed,
            'peak_memory' => $this->formatBytes($peakMemory),
            'peak_memory_bytes' => $peakMemory,
            'query_count' => $this->queryCount,
            'avg_query_time' => $this->getAverageQueryTime(),
            'slowest_query' => $this->getSlowestQuery()
        ];
    }
    
    /**
     * Get average query execution time
     */
    private function getAverageQueryTime() {
        if (empty($this->queries)) return 0;
        
        $total = array_sum(array_column($this->queries, 'time'));
        return round($total / count($this->queries), 4);
    }
    
    /**
     * Get slowest query
     */
    private function getSlowestQuery() {
        if (empty($this->queries)) return null;
        
        usort($this->queries, function($a, $b) {
            return $b['time'] <=> $a['time'];
        });
        
        $slowest = $this->queries[0];
        return [
            'query' => substr($slowest['query'], 0, 200) . '...',
            'time' => round($slowest['time'], 4)
        ];
    }
    
    /**
     * Log performance to database
     */
    public function logToDatabase($endpoint, $userType = null, $userId = null) {
        if (!$this->logToDatabase || !$this->connection) return;
        
        $stats = $this->getStats();
        
        try {
            $stmt = $this->connection->prepare(
                "INSERT INTO performance_log 
                (endpoint, execution_time, memory_usage, query_count, user_type, user_id) 
                VALUES (?, ?, ?, ?, ?, ?)"
            );
            
            if ($stmt) {
                $stmt->bind_param(
                    'sdissi',
                    $endpoint,
                    $stats['execution_time'],
                    $stats['memory_used_bytes'],
                    $stats['query_count'],
                    $userType,
                    $userId
                );
                $stmt->execute();
                $stmt->close();
            }
        } catch (Exception $e) {
            error_log("Performance logging failed: " . $e->getMessage());
        }
    }
    
    /**
     * Display performance info as HTML comment (for debugging)
     */
    public function displayAsComment() {
        if (!$this->enabled) return '';
        
        $stats = $this->getStats();
        
        $output = "\n<!-- PERFORMANCE METRICS\n";
        $output .= "Execution Time: {$stats['execution_time_ms']} ms\n";
        $output .= "Memory Used: {$stats['memory_used']}\n";
        $output .= "Peak Memory: {$stats['peak_memory']}\n";
        $output .= "Database Queries: {$stats['query_count']}\n";
        
        if ($stats['avg_query_time'] > 0) {
            $output .= "Avg Query Time: {$stats['avg_query_time']} s\n";
        }
        
        if ($stats['slowest_query']) {
            $output .= "Slowest Query: {$stats['slowest_query']['time']} s\n";
            $output .= "Query: {$stats['slowest_query']['query']}\n";
        }
        
        $output .= "-->\n";
        
        return $output;
    }
    
    /**
     * Display performance bar (for admin/dev)
     */
    public function displayBar() {
        if (!$this->enabled) return '';
        
        $stats = $this->getStats();
        
        // Color code based on performance
        $color = '#4CAF50'; // Green
        if ($stats['execution_time'] > 1) $color = '#ff9800'; // Orange
        if ($stats['execution_time'] > 2) $color = '#f44336'; // Red
        
        $html = '<div style="position:fixed;bottom:0;left:0;right:0;background:' . $color . ';color:white;padding:8px;font-size:12px;z-index:9999;font-family:monospace;display:flex;justify-content:space-between;box-shadow:0 -2px 5px rgba(0,0,0,0.2);">';
        $html .= '<span><strong>⚡ Performance:</strong> ' . $stats['execution_time_ms'] . ' ms</span>';
        $html .= '<span><strong>💾 Memory:</strong> ' . $stats['memory_used'] . '</span>';
        $html .= '<span><strong>🗄️ Queries:</strong> ' . $stats['query_count'] . '</span>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes) {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
    
    /**
     * Check if page is slow
     */
    public function isPageSlow($threshold = 1.0) {
        $executionTime = microtime(true) - $this->startTime;
        return $executionTime > $threshold;
    }
    
    /**
     * Get performance recommendations
     */
    public function getRecommendations() {
        $stats = $this->getStats();
        $recommendations = [];
        
        if ($stats['execution_time'] > 2) {
            $recommendations[] = "Page load time is slow (" . $stats['execution_time_ms'] . " ms). Consider optimization.";
        }
        
        if ($stats['query_count'] > 20) {
            $recommendations[] = "High number of queries ({$stats['query_count']}). Consider query optimization or caching.";
        }
        
        if ($stats['memory_used_bytes'] > 50 * 1024 * 1024) { // 50MB
            $recommendations[] = "High memory usage ({$stats['memory_used']}). Check for memory leaks.";
        }
        
        if ($stats['slowest_query'] && $stats['slowest_query']['time'] > 0.5) {
            $recommendations[] = "Slow query detected ({$stats['slowest_query']['time']} s). Consider adding indexes.";
        }
        
        return $recommendations;
    }
}

/**
 * Simple wrapper to track query execution time
 */
function trackQueryExecution($connection, $query) {
    $monitor = PerformanceMonitor::getInstance();
    
    $startTime = microtime(true);
    $result = $connection->query($query);
    $executionTime = microtime(true) - $startTime;
    
    $monitor->trackQuery($query, $executionTime);
    
    return $result;
}

?>




