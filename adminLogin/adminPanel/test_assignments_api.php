<?php
session_start();
$_SESSION['adminLoggedIn'] = true; // Simulate admin login for testing

include "../../db_connection/dlhs_db_connection.php";

echo "<h3>Testing Subject Teacher Assignments API</h3>";

// Check if table exists and has data
$check = "SELECT COUNT(*) as total, 
                 SUM(CASE WHEN isActive = 1 THEN 1 ELSE 0 END) as active_count
          FROM subject_teacher_assignment";
$result = $connection->query($check);
$row = $result->fetch_assoc();

echo "<p>Total assignments in database: " . $row['total'] . "</p>";
echo "<p>Active assignments: " . $row['active_count'] . "</p>";

// Test the actual query from the API
$query = "SELECT sta.assignmentId, yg.yearGroupName, c.className, s.subjectName, 
                 CONCAT(UPPER(sl.surname), ' ', sl.firstName, ' ', sl.middleName) as teacherName
          FROM subject_teacher_assignment sta
          JOIN classes c ON sta.classId = c.classId
          JOIN yeargroup yg ON c.classYearGroup = yg.yearGroupId
          JOIN subjects s ON sta.subjectId = s.subjectId
          JOIN stafflogin sl ON sta.teacherId = sl.staffId
          WHERE sta.isActive = 1
          ORDER BY yg.yearGroupName, c.className, s.subjectName";

$result = $connection->query($query);

if($result) {
    echo "<p>Query executed successfully. Rows returned: " . $result->num_rows . "</p>";
    
    if($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Year Group</th><th>Class</th><th>Subject</th><th>Teacher</th></tr>";
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['assignmentId'] . "</td>";
            echo "<td>" . $row['yearGroupName'] . "</td>";
            echo "<td>" . $row['className'] . "</td>";
            echo "<td>" . $row['subjectName'] . "</td>";
            echo "<td>" . $row['teacherName'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red;'>No active assignments found.</p>";
    }
} else {
    echo "<p style='color:red;'>Query error: " . $connection->error . "</p>";
}

// Test the JSON response
echo "<hr><h4>Testing JSON API Response:</h4>";
include "getSubjectTeacherAssignments.php";
?>
