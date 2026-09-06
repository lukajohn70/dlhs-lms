<?php
session_start();

echo "<h2>Session Information</h2>";
echo "<pre>";
echo "staffLoggedIn: " . (isset($_SESSION['staffLoggedIn']) ? 'Yes' : 'No') . "\n";
echo "staffId: " . ($_SESSION['staffId'] ?? 'Not set') . "\n";
echo "staffName: " . ($_SESSION['staffName'] ?? 'Not set') . "\n";
echo "\nAll session data:\n";
print_r($_SESSION);
echo "</pre>";

// Check assignments for this teacher
if(isset($_SESSION['staffId'])){
    require_once '../../db_connection/dlhs_db_connection.php';
    
    $staffId = $_SESSION['staffId'];
    $query = "SELECT * FROM v_my_subject_assignments WHERE teacherId = $staffId";
    $result = $connection->query($query);
    
    echo "<h3>Assignments for Teacher ID: $staffId</h3>";
    if($result->num_rows > 0){
        echo "<table border='1'><tr><th>Subject</th><th>Class</th><th>Session</th></tr>";
        while($row = $result->fetch_assoc()){
            echo "<tr>";
            echo "<td>" . $row['subjectName'] . "</td>";
            echo "<td>" . $row['className'] . "</td>";
            echo "<td>" . $row['academicSession'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red;'><strong>NO ASSIGNMENTS FOUND FOR THIS TEACHER!</strong></p>";
        echo "<p>You need to assign subjects to this teacher in the database.</p>";
        
        // Show available subjects and classes
        echo "<h4>Available Subjects:</h4>";
        $subjects = $connection->query("SELECT subjectId, subjectName FROM subjects ORDER BY subjectName LIMIT 10");
        echo "<ul>";
        while($sub = $subjects->fetch_assoc()){
            echo "<li>ID: {$sub['subjectId']} - {$sub['subjectName']}</li>";
        }
        echo "</ul>";
        
        echo "<h4>Available Classes:</h4>";
        $classes = $connection->query("SELECT yearGroupId, yearGroupName FROM yeargroup ORDER BY yearGroupName LIMIT 10");
        echo "<ul>";
        while($cls = $classes->fetch_assoc()){
            echo "<li>ID: {$cls['yearGroupId']} - {$cls['yearGroupName']}</li>";
        }
        echo "</ul>";
        
        echo "<h4>SQL to Add Assignment:</h4>";
        echo "<pre style='background:#f0f0f0; padding:10px;'>";
        echo "INSERT INTO subject_teacher_assignment \n";
        echo "    (classId, subjectId, teacherId, assignedDate, isActive, academicSession, assignedBy, notes)\n";
        echo "VALUES \n";
        echo "    ([CLASS_ID], [SUBJECT_ID], $staffId, NOW(), 1, '2025/2026', 1, 'Initial assignment');\n";
        echo "</pre>";
    }
}
?>
