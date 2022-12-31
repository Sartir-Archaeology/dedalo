<?php
/**
* DBI
* DB CONNECTION
* To close connection, use pg_close(DBi::_getConnection()); at end of page
*/
abstract class DBi {



	/**
	* _GETCONNECTION
	* Returns an PgSql\Connection instance on success, or false on failure.
	* @return resource|object $pg_conn
	* 8.1.0	Returns an PgSql\Connection instance now; previously, a resource was returned.
	*/
	public static function _getConnection(
		string|null $host		= DEDALO_HOSTNAME_CONN,
		string 		$user		= DEDALO_USERNAME_CONN,
		string 		$password	= DEDALO_PASSWORD_CONN,
		string 		$database	= DEDALO_DATABASE_CONN,
		string|null $port		= DEDALO_DB_PORT_CONN,
		string|null $socket		= DEDALO_SOCKET_CONN,
		bool 		$cache		= true
		) : object|false {

		static $pg_conn;

		if($cache===true && isset($pg_conn)) {
			return($pg_conn);
		}

		// basic str_connect with mandatory vars
		$str_connect = "dbname=$database user=$user password=$password";

		// Port is optional
		if($port!==null) {
			$str_connect = "port=$port ".$str_connect;
		}

		// Host is optional. When false, use default socket connection
		if($host!==null) {
			$str_connect = "host=$host ".$str_connect;
		}

		// Connecting, selecting database
		$pg_conn_real = pg_connect($str_connect);
		if($pg_conn===false) {
			debug_log(__METHOD__.' Error. Could not connect to database (52) : '.to_string($database), logger::ERROR);
			if(SHOW_DEBUG===true) {
				// throw new Exception("Error. Could not connect to database (52)", 1);
			}
		}

		// no cache case return fresh connection
			if ($cache!==true) {
				return $pg_conn_real;
			}

		// set as static
			$pg_conn = $pg_conn_real;


		return $pg_conn;
	}//end _getConnection



	/**
	* _GETNEWCONNECTION
	* Alias of _getConnection, but with param cache=false
	* Get a new PostgreSQL database connection without reuse existing connections
	* @return resource|object $pg_conn (object in PHP >=8.1)
	* 8.1.0	Returns an PgSql\Connection instance now; previously, a resource was returned.
	*/
	public static function _getNewConnection(
		string|null $host		= DEDALO_HOSTNAME_CONN,
		string 		$user		= DEDALO_USERNAME_CONN,
		string 		$password	= DEDALO_PASSWORD_CONN,
		string 		$database	= DEDALO_DATABASE_CONN,
		string|null $port		= DEDALO_DB_PORT_CONN,
		string|null $socket		= DEDALO_SOCKET_CONN
		) : object|false {

		$pg_conn = DBi::_getConnection(
			$host,
			$user,
			$password,
			$database,
			$port,
			$socket,
			false // bool use cache (!
		);

		return $pg_conn;
	}//end _getNewConnection



	/**
	* _GETCONNECTIONPDO
	* Returns an PgSql\Connection instance on success, or false on failure.
	* @return resource|object $pg_conn (object in PHP >=8.1)
	* 8.1.0	Returns an PgSql\Connection instance now; previously, a resource was returned.
	*/
	public static function _getConnectionPDO(
		string|null $host	= DEDALO_HOSTNAME_CONN,
		string $user		= DEDALO_USERNAME_CONN,
		string $password	= DEDALO_PASSWORD_CONN,
		string $database	= DEDALO_DATABASE_CONN,
		string|null $port	= DEDALO_DB_PORT_CONN,
		string|null $socket	= DEDALO_SOCKET_CONN,
		bool $cache			= true
		) : object|false {

		static $pg_pdo_conn;
		if($cache===true && isset($pg_pdo_conn)) {
			return($pg_pdo_conn);
		}

		// PDO
			try {
				$pg_pdo_conn = new PDO(
				'pgsql:host=' . $host . ';dbname=' . $database . ';', $user, $password, array(
					PDO::ATTR_ERRMODE   =>  PDO::ERRMODE_EXCEPTION,
				));
			} catch (\PDOException $e) {
				throw new \PDOException($e->getMessage(), (int)$e->getCode());
			}

		return $pg_pdo_conn;
	}//end _getConnectionPDO



	/**
	* _GETCONNECTION_MYSQL
	* @return resource $mysqli
	*/
	public static function _getConnection_mysql(
		$host=MYSQL_DEDALO_HOSTNAME_CONN,
		$user=MYSQL_DEDALO_USERNAME_CONN,
		$password=MYSQL_DEDALO_PASSWORD_CONN,
		$database=MYSQL_DEDALO_DATABASE_CONN,
		$port=MYSQL_DEDALO_DB_PORT_CONN,
		$socket=MYSQL_DEDALO_SOCKET_CONN
		) : object|false {


		// cache
			static $mysqli;
			if(isset($mysqli)) {
				return($mysqli);
			}

		/*
			$mysqli = new mysqli($host, $user, $password, $database, $port);
			if ($mysqli->connect_errno) {
				echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
				die();
			}
			#echo $mysqli->host_info . "\n";

			return $mysqli;
			*/

		// You should enable error reporting for mysqli before attempting to make a connection
		// @see https://www.php.net/manual/en/mysqli-driver.report-mode.php
			mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
			// mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);

		// INIT
			// $mysqli = mysqli_init();
			$mysqli = new mysqli($host, $user, $password, $database, $port);

			if ($mysqli===false) {
				#die('Dedalo '.__METHOD__ . ' Failed mysqli_init');
				throw new Exception(' Dedalo '.__METHOD__ . ' Failed mysqli_init ', 1);
			}

		// $mysqli->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);

		// AUTOCOMMIT : SET AUTOCOMMIT (Needed for InnoDB save)
		if (!$mysqli->options(MYSQLI_INIT_COMMAND, 'SET AUTOCOMMIT = 1')) {
			// die('Dedalo '.'Setting MYSQLI_INIT_COMMAND failed');
			throw new Exception(' Connect Error. Setting MYSQLI_INIT_COMMAND failed ', 1);
		}

		// TIMEOUT : SET CONNECT_TIMEOUT
		if (!$mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10)) {
			// die('Dedalo '.'Setting MYSQLI_OPT_CONNECT_TIMEOUT failed');
			throw new Exception(' Connect Error. Setting MYSQLI_OPT_CONNECT_TIMEOUT failed ', 1);
		}

		// CONNECT
		if (!$mysqli->real_connect($host, $user, $password, $database,  $port, $socket)) {
			throw new Exception(' Connect Error on mysqli->real_connect '.mysqli_connect_errno().' - '.mysqli_connect_error(), 1);
			// die( wrap_pre('Dedalo '.'Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error()) );
		}

		// UTF8 : Change character set to utf8mb4
		if (!$mysqli->set_charset('utf8mb4')) {
			// printf("Error loading character set utf8mb4: %s\n", $mysqli->error);
			debug_log(__METHOD__." Error loading character set utf8mb4: ".to_string($mysqli->error), logger::DEBUG);
		}

		// errors
			// $errno = mysqli_connect_errno();
			// $error = mysqli_connect_error();
			// 	dump($errno, '$errno ++ '.to_string());
			// 	dump($error, '$error ++ '.to_string());


		return $mysqli;
	}//end _getConnection_mysql



}//end class DBi
