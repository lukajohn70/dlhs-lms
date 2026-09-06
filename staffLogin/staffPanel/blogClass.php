 <?php
class blogClass
{
	/* Blog Publish */
	public function blogPublish($question)
	{
		try
		{
			$db = getDB();
			$created=time();
			$stmt = $db->prepare("INSERT INTO js2mathstestquest143(question) VALUES(:question)");
			$stmt->bindParam("question", $question,PDO::PARAM_STR);
			$stmt->execute();
			$bid=$db->lastInsertId();
			$db = null;
			return 1;
		}
		catch(PDOException $e) 
		{
			echo '{"error":{"text":'. $e->getMessage() .'}}';
		}
	}
}
?>