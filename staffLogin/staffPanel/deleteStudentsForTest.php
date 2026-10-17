<?php
session_start();
require_once 'sessionTime.php';
if ((time() - $_SESSION['staffLast_login']) > $allottedTime) {
	require_once 'unsetSessions.php';
	echo 0;
} else {
	session_write_close();
	require_once "../../db_connection/dlhs_db_connection.php";

	if (isset($_POST['checkedForDelete'])) {
		$checkedForDelete = mysqli_real_escape_string($connection, $_POST['checkedForDelete']);
		$str_arr = explode(",", $checkedForDelete);

		// Group examinee IDs by testId
		$byTest = array();
		foreach ($str_arr as $chk1) {
			$parts = explode("n", trim($chk1));
			if (count($parts) >= 2) {
				$tId = (int)$parts[0];
				$exId = (int)$parts[1];
				if ($tId > 0 && $exId > 0) {
					$byTest[$tId][] = $exId;
				}
			}
		}

		foreach ($byTest as $tId => $exIds) {
			$qTest = "SELECT examineesTableName FROM tests WHERE testId = '$tId'";
			$rTest = $connection->query($qTest);
			if ($rTest && $rowTest = $rTest->fetch_assoc()) {
				$tbl = $rowTest['examineesTableName'];
				if (!empty($tbl) && preg_match('/^[a-zA-Z0-9_]+$/', $tbl)) {
					$idList = implode(',', $exIds);
					$connection->query("DELETE FROM `{$tbl}` WHERE examineeId IN ({$idList})");
				}
			}
		}

		echo 1;
	}
}
?>