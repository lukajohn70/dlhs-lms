<?php
 session_start();
require_once dirname(__DIR__, 2) . '/config/database.php';

$dlhsDbConfig = dlhs_database_config();

/* DATABASE CONFIGURATION */
define('DB_SERVER', $dlhsDbConfig['host']);
define('DB_USERNAME', $dlhsDbConfig['user']);
define('DB_PASSWORD', $dlhsDbConfig['password']);
define('DB_DATABASE', $dlhsDbConfig['database']);
define('DB_PORT', $dlhsDbConfig['port']);
define('DB_SOCKET', $dlhsDbConfig['socket']);
define('DB_CHARSET', $dlhsDbConfig['charset']);
//define("BASE_URL", "http://localhost/PHPLoginHash/"); // Eg. http://yourwebsite.com

function getDB()
{
	$dbhost=DB_SERVER;
	$dbuser=DB_USERNAME;
	$dbpass=DB_PASSWORD;
	$dbname=DB_DATABASE;
	$dbport=DB_PORT;
	$dbsocket=DB_SOCKET;
	$dbcharset=DB_CHARSET;
	try
	{
		if ($dbsocket !== '') {
			$dsn = "mysql:unix_socket=$dbsocket;dbname=$dbname;charset=$dbcharset";
		} else {
			$dsn = "mysql:host=$dbhost;port=$dbport;dbname=$dbname;charset=$dbcharset";
		}
		$dbConnection = new PDO($dsn, $dbuser, $dbpass);
		$dbConnection->exec("set names $dbcharset");
		$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $dbConnection;
	}
	catch (PDOException $e) 
	{
		echo 'Connection failed: ' . $e->getMessage();
	}
}
?>
