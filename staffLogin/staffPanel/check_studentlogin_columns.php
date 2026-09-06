<?php
require_once "../../db_connection/dlhs_db_connection.php";

echo "<h2>StudentLogin Table Structure</h2>";
$result = $connection->query("SHOW COLUMNS FROM studentlogin");
echo "<table border='1'><tr><th>Column Name</th><th>Type</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td><strong>{$row['Field']}</strong></td><td>{$row['Type']}</td></tr>";
}
echo "</table>";

echo "<h3>Sample Data</h3>";
$sample = $connection->query("SELECT * FROM studentlogin LIMIT 3");
if ($sample && $sample->num_rows > 0) {
    $first = $sample->fetch_assoc();
    echo "<table border='1'><tr>";
    foreach ($first as $key => $value) {
        echo "<th>$key</th>";
    }
    echo "</tr>";
    $sample->data_seek(0);
    while ($row = $sample->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>$value</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}
?>

