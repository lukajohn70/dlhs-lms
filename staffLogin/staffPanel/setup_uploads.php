<?php
/**
 * One-time server setup script
 * Visit this URL once to create/fix upload directories
 * DELETE THIS FILE after running it!
 */

$baseDir = realpath(__DIR__ . '/../..') . '/';
$dirs = [
    $baseDir . 'uploads/',
    $baseDir . 'uploads/resources/',
    $baseDir . 'uploads/file_requests/',
];

echo "<h2>DLHS Upload Directory Setup</h2>";
echo "<pre>";

foreach ($dirs as $dir) {
    echo "Checking: $dir\n";

    // Create if not exists
    if (!is_dir($dir)) {
        if (mkdir($dir, 0777, true)) {
            echo "  ✅ Created directory\n";
        } else {
            echo "  ❌ FAILED to create directory — check parent permissions\n";
            continue;
        }
    } else {
        echo "  ℹ️  Directory already exists\n";
    }

    // Fix permissions
    if (chmod($dir, 0777)) {
        echo "  ✅ Permissions set to 0777\n";
    } else {
        echo "  ⚠️  chmod() failed (may need to run as root via SSH)\n";
    }

    // Confirm writable
    if (is_writable($dir)) {
        echo "  ✅ WRITABLE — uploads will work!\n";
    } else {
        echo "  ❌ NOT WRITABLE — SSH fix needed (see below)\n";
    }

    echo "\n";
}

echo "</pre>";
echo "<hr>";
echo "<h3>If any directory is NOT WRITABLE, run this via SSH on the server:</h3>";
echo "<pre style='background:#111;color:#0f0;padding:15px;border-radius:8px;'>";
echo htmlspecialchars("sudo chown -R www-data:www-data /var/www/html/dlhs/uploads/\n");
echo htmlspecialchars("sudo chmod -R 775 /var/www/html/dlhs/uploads/\n");
echo "</pre>";
echo "<p style='color:red;font-weight:bold;'>⚠️ DELETE THIS FILE from the server after running it!</p>";
echo "<p>Path of this file: " . __FILE__ . "</p>";
?>
