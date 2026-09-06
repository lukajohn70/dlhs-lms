<?php
require_once "db_connection/dlhs_db_connection.php";

echo "Checking smartboard_sessions columns...\n";

$columnsToCheck = [
    'videoCurrentTime' => 'DECIMAL(10,2) DEFAULT 0',
    'videoDuration' => 'DECIMAL(10,2) DEFAULT 0',
    'videoIsPaused' => 'TINYINT(1) DEFAULT 0',
    'videoIsMuted' => 'TINYINT(1) DEFAULT 0',
    'videoCommand' => 'VARCHAR(50) DEFAULT NULL',
    'videoCommandValue' => 'VARCHAR(50) DEFAULT NULL'
];

$result = $connection->query("SHOW COLUMNS FROM smartboard_sessions");
$existingColumns = [];
while ($row = $result->fetch_assoc()) {
    $existingColumns[] = $row['Field'];
}

foreach ($columnsToCheck as $colName => $colType) {
    if (!in_array($colName, $existingColumns)) {
        echo "Adding column $colName...\n";
        $connection->query("ALTER TABLE smartboard_sessions ADD COLUMN $colName $colType");
        if ($connection->error) {
            echo "Error adding $colName: " . $connection->error . "\n";
        } else {
            echo "Successfully added $colName.\n";
        }
    } else {
        echo "Column $colName already exists.\n";
    }
}
echo "Migration complete.\n";
