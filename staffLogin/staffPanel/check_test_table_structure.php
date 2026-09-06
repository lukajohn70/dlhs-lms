<?php
require_once "../../db_connection/dlhs_db_connection.php";

// Get one completed test to examine
$testQuery = "SELECT tableName, examineesTableName, answersTable 
              FROM tests 
              WHERE status = 2 
              LIMIT 1";
$testResult = $connection->query($testQuery);

if ($testResult && $testResult->num_rows > 0) {
    $test = $testResult->fetch_assoc();
    
    echo "<h2>Test Table Structures</h2>";
    
    // Check Questions Table
    $questionsTable = $test['tableName'];
    echo "<h3>Questions Table: $questionsTable</h3>";
    $result = $connection->query("SHOW COLUMNS FROM `$questionsTable`");
    echo "<table border='1'><tr><th>#</th><th>Column Name</th><th>Type</th></tr>";
    $i = 0;
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>$i</td><td><strong>{$row['Field']}</strong></td><td>{$row['Type']}</td></tr>";
        $i++;
    }
    echo "</table>";
    
    // Check Examinees/Tested Table
    $testedTable = $test['examineesTableName'];
    echo "<h3>Examinees/Tested Table: $testedTable</h3>";
    $result = $connection->query("SHOW COLUMNS FROM `$testedTable`");
    echo "<table border='1'><tr><th>#</th><th>Column Name</th><th>Type</th></tr>";
    $i = 0;
    while ($row = $result->fetch_assoc()) {
        $highlight = stripos($row['Field'], 'question') !== false || 
                     stripos($row['Field'], 'array') !== false ||
                     stripos($row['Field'], 'serial') !== false ? 
                     'background:yellow;' : '';
        echo "<tr style='$highlight'><td>$i</td><td><strong>{$row['Field']}</strong></td><td>{$row['Type']}</td></tr>";
        $i++;
    }
    echo "</table>";
    
    // Sample data from tested table
    echo "<h3>Sample Data from $testedTable</h3>";
    $sampleData = $connection->query("SELECT * FROM `$testedTable` LIMIT 2");
    if ($sampleData && $sampleData->num_rows > 0) {
        echo "<pre>";
        while ($row = $sampleData->fetch_assoc()) {
            print_r($row);
            echo "\n---\n";
        }
        echo "</pre>";
    }
    
    // Check Answers Table
    $answersTable = $test['answersTable'];
    echo "<h3>Answers Table: $answersTable</h3>";
    $result = $connection->query("SHOW COLUMNS FROM `$answersTable`");
    echo "<table border='1'><tr><th>#</th><th>Column Name</th><th>Type</th></tr>";
    $i = 0;
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>$i</td><td><strong>{$row['Field']}</strong></td><td>{$row['Type']}</td></tr>";
        $i++;
    }
    echo "</table>";
    
} else {
    echo "No completed tests found!";
}
?>

