<?php
session_start();
require_once '../../db_connection/dlhs_db_connection.php';
require_once '../../scripts/online_tracking_schema.php';

dlhsEnsureOnlineTrackingTables($connection);

echo "<h2>Tracking Debug Info</h2>";

// Check session
echo "<h3>1. Session Info:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Admin ID: " . ($_SESSION['adminId'] ?? 'NOT SET') . "<br>";
echo "Staff ID: " . ($_SESSION['staffId'] ?? 'NOT SET') . "<br>";
echo "Student ID: " . ($_SESSION['studentId'] ?? 'NOT SET') . "<br>";

// Check database connection
echo "<h3>2. Database Connection:</h3>";
if ($connection) {
    echo "✓ Connected to database<br>";
} else {
    echo "✗ NOT connected to database<br>";
}

// Check if tables exist
echo "<h3>3. Tables Check:</h3>";
$result = $connection->query("SHOW TABLES LIKE 'online_users'");
if ($result->num_rows > 0) {
    echo "✓ online_users table exists<br>";
} else {
    echo "✗ online_users table DOES NOT exist<br>";
}

$result = $connection->query("SHOW TABLES LIKE 'active_test_takers'");
if ($result->num_rows > 0) {
    echo "✓ active_test_takers table exists<br>";
} else {
    echo "✗ active_test_takers table DOES NOT exist<br>";
}

// Try to track presence manually
echo "<h3>4. Manual Tracking Test:</h3>";
try {
    require_once '../../track_user_presence.php';
    echo "✓ Tracking script executed successfully<br>";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

// Check if record was created
echo "<h3>5. Records in online_users:</h3>";
$result = $connection->query("SELECT * FROM online_users");
echo "Total records: " . $result->num_rows . "<br><br>";

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Type</th><th>Full Name</th><th>Current Page</th><th>Last Activity</th><th>Active</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['userId'] . "</td>";
        echo "<td>" . $row['userType'] . "</td>";
        echo "<td>" . $row['fullName'] . "</td>";
        echo "<td>" . $row['currentPage'] . "</td>";
        echo "<td>" . $row['lastActivity'] . "</td>";
        echo "<td>" . ($row['isActive'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No records found in online_users table!</p>";
}

// Check for your specific record
echo "<h3>6. Your Record:</h3>";
if (isset($_SESSION['adminId'])) {
    $adminId = $_SESSION['adminId'];
    $stmt = $connection->prepare("SELECT * FROM online_users WHERE userId = ? AND userType = 'admin'");
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "✓ Found your record!<br>";
        $row = $result->fetch_assoc();
        echo "<pre>" . print_r($row, true) . "</pre>";
    } else {
        echo "✗ Your record NOT found in database<br>";
        echo "Looking for adminId: " . $adminId . "<br>";
    }
}

echo "<br><a href='online_users.php'>Go to Online Users Dashboard</a>";
?>

