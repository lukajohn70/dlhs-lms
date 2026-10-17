<?php
// DLHS Result Tables Migration Runner
// Access via browser: http://localhost/dlhs/run_results_migration.php
// DELETE this file after running!
session_start();
require_once __DIR__ . '/db_connection/dlhs_db_connection.php';

$sql = file_get_contents(__DIR__ . '/scripts/create_results_tables.sql');
$statements = array_filter(array_map('trim', explode(';', $sql)));

$results = [];
$errors  = [];
foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if (!$stmt || strpos($stmt, '--') === 0) continue;
    try {
        $connection->query($stmt);
        $results[] = htmlspecialchars(substr($stmt, 0, 80)) . '...';
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>DLHS Migration</title>
<style>
body { font-family: sans-serif; padding: 30px; background: #f4f7f6; }
.ok  { background:#d4edda; color:#155724; padding:8px 12px; border-radius:6px; margin:4px 0; }
.err { background:#f8d7da; color:#721c24; padding:8px 12px; border-radius:6px; margin:4px 0; }
h2   { color: #003366; }
</style>
</head>
<body>
<h2>DLHS Result Tables Migration</h2>
<?php if ($errors): ?>
    <h3 style="color:red">Errors</h3>
    <?php foreach($errors as $e): ?><div class="err"><?= $e ?></div><?php endforeach; ?>
<?php endif; ?>
<?php if ($results): ?>
    <h3 style="color:green">Executed Successfully</h3>
    <?php foreach($results as $r): ?><div class="ok">✓ <?= $r ?></div><?php endforeach; ?>
<?php endif; ?>
<?php if (!$errors): ?>
    <div style="margin-top:20px; padding:15px; background:#003366; color:#ffd700; border-radius:8px;">
        <strong>✓ Migration complete!</strong> Both tables created. Please delete this file now.
    </div>
<?php endif; ?>
</body>
</html>
