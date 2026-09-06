<?php
require_once __DIR__ . '/../db_connection/dlhs_db_connection.php';

echo "Adding video control columns to smartboard_sessions...\n";

$queries = [
    "ALTER TABLE `smartboard_sessions` ADD COLUMN `videoCommand` VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE `smartboard_sessions` ADD COLUMN `videoCommandValue` VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE `smartboard_sessions` ADD COLUMN `videoCurrentTime` FLOAT DEFAULT 0",
    "ALTER TABLE `smartboard_sessions` ADD COLUMN `videoDuration` FLOAT DEFAULT 0",
    "ALTER TABLE `smartboard_sessions` ADD COLUMN `videoIsPaused` TINYINT DEFAULT 1",
    "ALTER TABLE `smartboard_sessions` ADD COLUMN `videoIsMuted` TINYINT DEFAULT 0"
];

foreach ($queries as $q) {
    if ($connection->query($q)) {
        echo "✔ Success: $q\n";
    } else {
        echo "⚠ Note/Error: " . $connection->error . "\n";
    }
}

echo "Done!\n";
?>
