<?php
/**
 * DLHS Batch Password Hasher
 * 
 * Safely iterates through studentlogin, stafflogin, and admintable,
 * converts all legacy plaintext passwords to secure Bcrypt hashes,
 * and leaves existing hashes untouched.
 */
require_once __DIR__ . '/../db_connection/dlhs_db_connection.php';

echo "=== DLHS Batch Password Migration ===\n\n";

function hashTablePasswords($connection, $table, $idCol, $passwordCol) {
    echo "Processing `{$table}`...\n";
    $query = "SELECT `{$idCol}`, `{$passwordCol}` FROM `{$table}`";
    $result = $connection->query($query);
    if (!$result) {
        echo "  [ERROR] Failed to query `{$table}`: " . $connection->error . "\n";
        return;
    }

    $total = 0;
    $migrated = 0;
    $alreadyHashed = 0;

    $updStmt = $connection->prepare("UPDATE `{$table}` SET `{$passwordCol}` = ? WHERE `{$idCol}` = ?");

    while ($row = $result->fetch_assoc()) {
        $total++;
        $id = $row[$idCol];
        $pwd = (string)$row[$passwordCol];

        // If it starts with $2y$, $2a$, $2b$, or $argon2, it's already a modern hash
        if (preg_match('/^\$(2[aby]|argon2id?)\$/', $pwd)) {
            $alreadyHashed++;
            continue;
        }

        if ($pwd === '') {
            continue;
        }

        $newHash = password_hash($pwd, PASSWORD_DEFAULT);
        $updStmt->bind_param("ss", $newHash, $id);
        $updStmt->execute();
        $migrated++;
    }

    $updStmt->close();
    echo "  Total: {$total} | Migrated to Bcrypt: {$migrated} | Already Hashed: {$alreadyHashed}\n\n";
}

hashTablePasswords($connection, 'admintable', 'adminId', 'password');
hashTablePasswords($connection, 'stafflogin', 'staffId', 'password');
hashTablePasswords($connection, 'studentlogin', 'studentId', 'password');

echo "=== Password Migration Complete! ===\n";
?>
