<?php
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
	@session_start();
}
if (session_status() === PHP_SESSION_ACTIVE) {
	session_write_close();
}

ini_set('display_errors', '0');
header('Content-Type: text/html; charset=utf-8');

include "../../db_connection/dlhs_db_connection.php";
	
if(isset($_POST["yearGroup"]))
{
	// Capture selected year group
	$yearGroup = intval($_POST["yearGroup"]);
	$yearGroupName = htmlspecialchars(trim($_POST["yearGroupName"] ?? ''));

	if ($yearGroup > 0)
	{
		$lgas = "SELECT classId, classYearGroup, className FROM classes WHERE classYearGroup='$yearGroup' ORDER BY className ASC";
		$result = $connection->query($lgas);
		if ($result && $result->num_rows > 0)
		{
			$groupLabel = $yearGroupName !== '' ? ' from ' . $yearGroupName : '';
			echo "<option value=''>... Select Class" . $groupLabel . " ...</option>";
			while($row = $result->fetch_array(MYSQLI_NUM)){
				echo "<option value='" . htmlspecialchars($row[0]) . "'>" . htmlspecialchars($row[2]) . "</option>";
			}
		}
		else
		{
			echo "<option value=''>No class found</option>";
		}
	}
	else
	{
		echo "<option value=''>... Select Class ...</option>";
	}
}
?>
