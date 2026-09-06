<?php
/**
 * Diagnostic: Check Specific Test Table
 */
require_once "../../db_connection/dlhs_db_connection.php";

$tableName = isset($_GET['table']) ? $_GET['table'] : 'basic8frecat1ft2526tested793';

echo "<html><head><title>Test Table Diagnostic</title>";
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
echo "<style>
    body { font-family: Arial; padding: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #4CAF50; color: white; }
    pre { background: #f4f4f4; padding: 10px; overflow-x: auto; }
    .highlight { background: #ffffcc; font-weight: bold; }
</style></head><body>";

echo "<h1>Test Table Diagnostic: $tableName</h1>";

// Check if table exists
$checkTable = $connection->query("SHOW TABLES LIKE '$tableName'");
if (!$checkTable || $checkTable->num_rows == 0) {
    echo "<p style='color:red;'>❌ Table '$tableName' does not exist!</p>";
    echo "</body></html>";
    exit();
}

echo "<p style='color:green;'>✓ Table exists</p>";

// Show table structure
echo "<h2>📋 Table Structure (Columns)</h2>";
$columns = $connection->query("SHOW COLUMNS FROM `$tableName`");
echo "<table>";
echo "<tr><th>Column #</th><th>Column Name</th><th>Type</th><th>Description</th></tr>";
$colNum = 0;
$columnNames = [];
while ($col = $columns->fetch_assoc()) {
    $columnNames[$colNum] = $col['Field'];
    $description = '';
    if (stripos($col['Field'], 'score') !== false || stripos($col['Field'], 'mark') !== false) {
        $description = '← SCORE COLUMN';
    } elseif (stripos($col['Field'], 'user') !== false || stripos($col['Field'], 'examinee') !== false) {
        $description = '← STUDENT ID';
    } elseif (stripos($col['Field'], 'question') !== false || stripos($col['Field'], 'array') !== false) {
        $description = '← QUESTIONS';
    }
    echo "<tr>";
    echo "<td><strong>$colNum</strong></td>";
    echo "<td>{$col['Field']}</td>";
    echo "<td>{$col['Type']}</td>";
    echo "<td style='color:blue;'>$description</td>";
    echo "</tr>";
    $colNum++;
}
echo "</table>";

// Count total records
$countQuery = $connection->query("SELECT COUNT(*) as cnt FROM `$tableName`");
$totalRecords = $countQuery ? $countQuery->fetch_assoc()['cnt'] : 0;

echo "<h2>📊 Data Summary</h2>";
echo "<p><strong>Total Students Who Took Test:</strong> $totalRecords</p>";

// Sample data
echo "<h2>📄 Sample Data (First 5 Records)</h2>";
$sampleData = $connection->query("SELECT * FROM `$tableName` LIMIT 5");

if ($sampleData && $sampleData->num_rows > 0) {
    echo "<p><strong>Raw Data View:</strong></p>";
    echo "<pre>";
    $recordNum = 1;
    while ($row = $sampleData->fetch_assoc()) {
        echo "=== Record #$recordNum ===\n";
        foreach ($row as $key => $value) {
            $displayValue = $value;
            if (strlen($value) > 100) {
                $displayValue = substr($value, 0, 100) . "... (truncated)";
            }
            echo sprintf("%-30s : %s\n", $key, $displayValue);
        }
        echo "\n";
        $recordNum++;
    }
    echo "</pre>";
    
    // Check for score-related columns
    echo "<h2>🔍 Score Analysis</h2>";
    $sampleData->data_seek(0);
    $firstRecord = $sampleData->fetch_assoc();
    
    $scoreColumns = [];
    foreach ($firstRecord as $key => $value) {
        if (stripos($key, 'score') !== false || 
            stripos($key, 'mark') !== false || 
            stripos($key, 'total') !== false ||
            stripos($key, 'grade') !== false) {
            $scoreColumns[$key] = $value;
        }
    }
    
    if (empty($scoreColumns)) {
        echo "<p style='color:orange;'>⚠️ <strong>NO SCORE COLUMNS FOUND!</strong></p>";
        echo "<p>This table doesn't have columns for storing scores. Scores might be:</p>";
        echo "<ul>";
        echo "<li>Calculated dynamically from the answers table</li>";
        echo "<li>Stored in a different table</li>";
        echo "<li>Not yet calculated</li>";
        echo "</ul>";
    } else {
        echo "<p style='color:green;'>✓ Found score-related columns:</p>";
        echo "<table>";
        echo "<tr><th>Column Name</th><th>Sample Value</th></tr>";
        foreach ($scoreColumns as $col => $val) {
            echo "<tr><td><strong>$col</strong></td><td>$val</td></tr>";
        }
        echo "</table>";
    }
    
} else {
    echo "<p style='color:red;'>❌ No records found in this table! Test hasn't been taken yet.</p>";
}

// Get test info from main tests table
echo "<h2>🎯 Test Information</h2>";
$testInfo = $connection->query("SELECT * FROM tests WHERE examineesTableName = '$tableName'");
if ($testInfo && $testInfo->num_rows > 0) {
    $test = $testInfo->fetch_assoc();
    echo "<table>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    foreach ($test as $key => $value) {
        echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:orange;'>⚠️ Could not find test info in main tests table</p>";
}

// Check answers table
if ($testInfo && $testInfo->num_rows > 0) {
    $test = $testInfo->fetch_assoc();
    $answersTable = $test['answersTable'];
    echo "<h2>📝 Answers Table Check: $answersTable</h2>";
    
    $checkAnswers = $connection->query("SHOW TABLES LIKE '$answersTable'");
    if ($checkAnswers && $checkAnswers->num_rows > 0) {
        $answerCount = $connection->query("SELECT COUNT(*) as cnt FROM `$answersTable`");
        $totalAnswers = $answerCount ? $answerCount->fetch_assoc()['cnt'] : 0;
        echo "<p style='color:green;'>✓ Answers table exists with <strong>$totalAnswers</strong> answer records</p>";
        
        // Sample answers
        echo "<p><strong>Sample Answers:</strong></p>";
        $sampleAnswers = $connection->query("SELECT * FROM `$answersTable` LIMIT 5");
        echo "<pre>";
        $ansNum = 1;
        while ($ans = $sampleAnswers->fetch_assoc()) {
            echo "Answer #$ansNum:\n";
            print_r($ans);
            echo "\n";
            $ansNum++;
        }
        echo "</pre>";
    } else {
        echo "<p style='color:red;'>❌ Answers table does not exist!</p>";
    }
}

echo "<hr>";
echo "<h2>💡 Summary & Diagnosis</h2>";
echo "<div style='background:#f0f8ff; padding:15px; border-left:4px solid #2196F3;'>";
echo "<p><strong>What I Found:</strong></p>";
echo "<ul>";
echo "<li>Table: <strong>$tableName</strong></li>";
echo "<li>Total students who took test: <strong>$totalRecords</strong></li>";
echo "<li>Score columns found: <strong>" . (empty($scoreColumns) ? "NONE" : implode(', ', array_keys($scoreColumns))) . "</strong></li>";
echo "</ul>";

if (empty($scoreColumns) && $totalRecords > 0) {
    echo "<p style='color:#FF9800;'><strong>⚠️ ISSUE IDENTIFIED:</strong></p>";
    echo "<p>Students took the test ($totalRecords records) but there are NO score columns in this table.</p>";
    echo "<p><strong>This means:</strong></p>";
    echo "<ol>";
    echo "<li>Scores are calculated <strong>on-the-fly</strong> from the answers table when viewing results</li>";
    echo "<li>The results page (examineesStatus.php) needs to calculate scores dynamically</li>";
    echo "<li>If results aren't showing, the calculation code might have an error</li>";
    echo "</ol>";
}

echo "</div>";

echo "<p><a href='examineesStatus.php' style='padding:10px 20px; background:#4CAF50; color:white; text-decoration:none; border-radius:4px;'>← Back to Student Results</a></p>";

echo "</body></html>";
?>

