<?php
session_start();
require_once "../../db_connection/dlhs_db_connection.php";

if (isset($_POST['testId'])) {
    $testId = $connection->real_escape_string($_POST['testId']);
    
    // Check if essay exists for this test
    $query = "SELECT question FROM essay_questions WHERE testId='$testId' LIMIT 1";
    $result = $connection->query($query);
    
    $hasEssay = false;
    if ($result && $result->num_rows > 0) {
        $hasEssay = true;
    }
    
    echo json_encode(array('hasEssay' => $hasEssay));
} else {
    echo json_encode(array('hasEssay' => false));
}
?>




