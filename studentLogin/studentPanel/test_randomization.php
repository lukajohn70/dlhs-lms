<?php
/**
 * TEST RANDOMIZATION - Quick Debug Script
 * Check what randomization value is being read
 */
session_start();
require_once('../../db_connection/dlhs_db_connection.php');

// Get a test ID (use your actual test ID)
$testId = isset($_GET['testId']) ? $_GET['testId'] : 0;

if ($testId == 0) {
    echo "<h3>Usage: test_randomization.php?testId=YOUR_TEST_ID</h3>";
    echo "<p>Get your test ID from the tests list in staff panel</p>";
    exit;
}

echo "<h2>Randomization Debug for Test ID: $testId</h2>";
echo "<hr>";

// Get test data
$query = "SELECT * FROM tests WHERE testId=?";
$stmt = $connection->prepare($query);
$stmt->bind_param("i", $testId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_array(MYSQLI_NUM);

if (!$row) {
    echo "<p style='color:red;'>Test not found!</p>";
    exit;
}

echo "<h3>Column Values (by index):</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Index</th><th>Value</th><th>Column Name (guessed)</th></tr>";

$columnNames = [
    0 => 'testId',
    1 => 'staffId',
    2 => 'testName',
    3 => 'testDate',
    4 => 'duration',
    5 => 'startHour',
    6 => 'startMinute',
    7 => 'amOrPm',
    8 => 'subject',
    9 => 'yearGroup',
    10 => 'section',
    11 => 'status',
    12 => 'tableName',
    13 => 'examineesTableName',
    14 => 'nameWithoutRand',
    15 => 'answersTable',
    16 => 'academicYearId',
    17 => 'reviewOption',
    18 => 'essayOption',
    19 => 'essayTime',
    20 => 'invigilatorId',
    21 => 'randomizeQuestions',
    22 => 'randomizeOptions'
];

foreach ($row as $index => $value) {
    $highlight = '';
    if (isset($columnNames[$index]) && strpos($columnNames[$index], 'randomize') !== false) {
        $highlight = " style='background:yellow;font-weight:bold;'";
    }
    echo "<tr$highlight>";
    echo "<td><strong>$index</strong></td>";
    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
    echo "<td>" . ($columnNames[$index] ?? 'Unknown') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<h3>What exam.php is reading:</h3>";
$randomizeQuestions = $row[18] ?? 'No';
echo "<p><strong>Row[18]:</strong> " . htmlspecialchars($randomizeQuestions) . " <em>(Currently used in exam.php)</em></p>";

echo "<hr>";
echo "<h3>Analysis:</h3>";

// Find which index has randomizeQuestions
$foundAtIndex = null;
foreach ($row as $index => $value) {
    if ($value === 'Yes' || $value === 'No') {
        if (isset($columnNames[$index]) && $columnNames[$index] === 'randomizeQuestions') {
            $foundAtIndex = $index;
            break;
        }
    }
}

if ($row[18] === 'Yes') {
    echo "<p style='color:green;'><strong>✓ Question randomization is ENABLED (row[18] = 'Yes')</strong></p>";
    echo "<p>Questions should be randomizing. If they're not, the shuffle might not be happening.</p>";
} else if ($row[18] === 'No') {
    echo "<p style='color:orange;'><strong>⚠ Question randomization is DISABLED (row[18] = 'No')</strong></p>";
    echo "<p><strong>ACTION:</strong> Edit your test and set 'Randomize Questions' to 'Yes'</p>";
} else {
    echo "<p style='color:red;'><strong>✗ Row[18] is not 'Yes' or 'No', it's: '" . htmlspecialchars($row[18]) . "'</strong></p>";
    echo "<p>This might be the wrong column. Check the table above to find where randomizeQuestions actually is.</p>";
}

echo "<hr>";
echo "<h3>Quick Fix:</h3>";
echo "<p>If randomizeQuestions is at a different index than 18, you need to update exam.php line 44</p>";
echo "<p>Change: <code>\$randomizeQuestions = \$row[18] ?? 'No';</code></p>";
echo "<p>To: <code>\$randomizeQuestions = \$row[CORRECT_INDEX] ?? 'No';</code></p>";

$connection->close();
?>



