<?php
session_start();
	include "../../db_connection/dlhs_db_connection.php";
	
	
    if(isset($_POST["theStudentClass"]))
	{
		// Capture selected year group
		$theYearGroup = $_POST["theYearGroup"];
		$theStudentClass = $_POST["theStudentClass"];
		$theTestName = $_POST["theTestName"];

        $students="SELECT * FROM studentlogin WHERE yearGroupId='$theYearGroup' AND classId='$theStudentClass'";
		$result = $connection->query( $students);
		if (($result->num_rows)>0)
		{	
			echo "<input type='checkbox' id='checkUncheckAll' onClick='CheckUncheckAll()' /> &nbsp;(Select/Unselect) all <br>";
			echo "<h4 style='color:#000000;'><center>Check the boxes to select students that will write the <b>$theTestName</b> Test:</center></h4>";
			
			while($row = $result->fetch_array(MYSQLI_NUM)){
				echo "<input type='checkbox' name='rowSelectCheckBox[]' id='rowSelectCheckBox' value='$row[0]' /> &nbsp; $row[1] &nbsp; $row[2] &nbsp; $row[3]<br>";
			}
			
			echo "<br><button type='button' id='submitSelected' onClick='getChecked()' class='btn btn-primary'><i class='fa fa-sign-in'></i> Add Students</button>";
		}
		else
		{
			echo "<label style='color:red' id='idForNotMatchCriteria'>No record(s) match the selected search criteria</label>";
		}
    }
?>

