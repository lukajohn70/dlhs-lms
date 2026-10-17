<?php
include "../../db_connection/dlhs_db_connection.php";
$res = $connection->query("SELECT count(*) as total FROM subject_teacher_assignment");
if ($res) {
    $row = $res->fetch_assoc();
    echo "Total assignments: " . $row['total'];
} else {
    echo "Error: " . $connection->error;
}
?>
