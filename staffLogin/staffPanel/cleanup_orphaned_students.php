<?php
/**
 * Cleanup Utility: Remove Orphaned Students from Test Tables
 * 
 * This fixes the issue where deleted/moved students break test results
 */

session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";

if (!isset($_SESSION['staffLoggedIn']) && !isset($_SESSION['adminLoggedIn'])) {
    die('Unauthorized access');
}

$staffId = isset($_SESSION['staffId']) ? $_SESSION['staffId'] : 0;

echo "<html><head><title>Cleanup Orphaned Students</title>";
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
echo "<style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; }
    .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
    .success { color: #4CAF50; font-weight: bold; }
    .error { color: #F44336; font-weight: bold; }
    .warning { color: #FF9800; font-weight: bold; }
    .info { color: #2196F3; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background: #4CAF50; color: white; }
    tr:nth-child(even) { background: #f9f9f9; }
    .btn { padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 10px 5px; }
    .btn-danger { background: #F44336; }
    .btn:hover { opacity: 0.8; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🧹 Cleanup Orphaned Students from Test Tables</h1>";

echo "<p class='info'><strong>What this does:</strong> Finds and removes students from test tables who no longer exist in the studentlogin table (deleted or moved students).</p>";

$action = isset($_GET['action']) ? $_GET['action'] : 'scan';

if ($action == 'scan') {
    // Scan for orphaned records
    echo "<h2>📊 Scanning for Orphaned Records...</h2>";
    
    $query = "SELECT testId, testName, examineesTableName FROM tests WHERE status = 2 ORDER BY testDate DESC";
    $result = $connection->query($query);
    
    if (!$result || $result->num_rows == 0) {
        echo "<p class='warning'>No completed tests found.</p>";
    } else {
        $totalOrphaned = 0;
        $affectedTests = [];
        
        echo "<table>";
        echo "<tr><th>Test ID</th><th>Test Name</th><th>Tested Table</th><th>Total Students</th><th>Orphaned</th><th>Action</th></tr>";
        
        while ($test = $result->fetch_assoc()) {
            $testId = $test['testId'];
            $testName = $test['testName'];
            $testedTable = $test['examineesTableName'];
            
            if (empty($testedTable)) continue;
            
            // Check if table exists
            $checkTable = $connection->query("SHOW TABLES LIKE '$testedTable'");
            if (!$checkTable || $checkTable->num_rows == 0) continue;
            
            // Count total students in tested table
            $totalQuery = $connection->query("SELECT COUNT(*) as cnt FROM `$testedTable`");
            $totalStudents = $totalQuery ? $totalQuery->fetch_assoc()['cnt'] : 0;
            
            // Count orphaned students
            $orphanedQuery = "SELECT COUNT(*) as cnt FROM `$testedTable` t 
                             WHERE NOT EXISTS (
                                 SELECT 1 FROM studentlogin s WHERE s.studentId = t.examineeUserId
                             )";
            $orphanedResult = $connection->query($orphanedQuery);
            $orphanedCount = $orphanedResult ? $orphanedResult->fetch_assoc()['cnt'] : 0;
            
            $rowClass = $orphanedCount > 0 ? "style='background:#ffebee;'" : "";
            echo "<tr $rowClass>";
            echo "<td>$testId</td>";
            echo "<td>$testName</td>";
            echo "<td>$testedTable</td>";
            echo "<td>$totalStudents</td>";
            
            if ($orphanedCount > 0) {
                echo "<td class='error'>$orphanedCount orphaned!</td>";
                echo "<td><a href='?action=clean&testId=$testId&table=$testedTable' class='btn btn-danger' onclick='return confirm(\"Remove $orphanedCount orphaned records?\")'>Clean Now</a></td>";
                $totalOrphaned += $orphanedCount;
                $affectedTests[] = $testName;
            } else {
                echo "<td class='success'>✓ Clean</td>";
                echo "<td>-</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
        
        echo "<hr>";
        echo "<h3>📋 Summary</h3>";
        echo "<p><strong>Total Orphaned Records:</strong> <span class='error'>$totalOrphaned</span></p>";
        
        if ($totalOrphaned > 0) {
            echo "<p class='warning'><strong>Affected Tests:</strong> " . implode(', ', $affectedTests) . "</p>";
            echo "<p><strong>Impact:</strong> These tests may not display results correctly until orphaned records are removed.</p>";
            echo "<a href='?action=clean_all' class='btn btn-danger' onclick='return confirm(\"Clean ALL $totalOrphaned orphaned records?\")'>🧹 Clean All Tests</a>";
        } else {
            echo "<p class='success'>✓ All tests are clean! No orphaned records found.</p>";
        }
    }
    
} elseif ($action == 'clean') {
    // Clean specific test
    $testId = intval($_GET['testId']);
    $table = $connection->real_escape_string($_GET['table']);
    
    echo "<h2>🧹 Cleaning Test...</h2>";
    
    $deleteQuery = "DELETE FROM `$table` 
                   WHERE NOT EXISTS (
                       SELECT 1 FROM studentlogin s WHERE s.studentId = `$table`.examineeUserId
                   )";
    
    if ($connection->query($deleteQuery)) {
        $deleted = $connection->affected_rows;
        echo "<p class='success'>✓ Successfully removed $deleted orphaned record(s) from $table</p>";
    } else {
        echo "<p class='error'>✗ Error: " . $connection->error . "</p>";
    }
    
    echo "<a href='?action=scan' class='btn'>← Back to Scan</a>";
    
} elseif ($action == 'clean_all') {
    // Clean all tests
    echo "<h2>🧹 Cleaning All Tests...</h2>";
    
    $query = "SELECT testId, testName, examineesTableName FROM tests WHERE status = 2";
    $result = $connection->query($query);
    
    $totalCleaned = 0;
    
    while ($test = $result->fetch_assoc()) {
        $table = $test['examineesTableName'];
        if (empty($table)) continue;
        
        $checkTable = $connection->query("SHOW TABLES LIKE '$table'");
        if (!$checkTable || $checkTable->num_rows == 0) continue;
        
        $deleteQuery = "DELETE FROM `$table` 
                       WHERE NOT EXISTS (
                           SELECT 1 FROM studentlogin s WHERE s.studentId = `$table`.examineeUserId
                       )";
        
        if ($connection->query($deleteQuery)) {
            $deleted = $connection->affected_rows;
            if ($deleted > 0) {
                echo "<p class='success'>✓ {$test['testName']}: Removed $deleted orphaned record(s)</p>";
                $totalCleaned += $deleted;
            }
        }
    }
    
    echo "<hr>";
    echo "<h3>✅ Cleanup Complete!</h3>";
    echo "<p class='success'>Total records cleaned: <strong>$totalCleaned</strong></p>";
    echo "<a href='?action=scan' class='btn'>← Scan Again</a>";
}

echo "<hr>";
echo "<p><a href='performance_dashboard.php' class='btn'>← Back to Performance Dashboard</a></p>";
echo "</div></body></html>";
?>

