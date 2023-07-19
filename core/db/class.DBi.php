<?php
declare(strict_types=1);
/**
* DBI
* DB CONNECTION
* To close connection, use pg_close(DBi::_getConnection()); at end of page
*/
abstract class DBi {



	/**
	* _GETCONNECTION
	* Returns an PgSql\Connection instance on success, or false on failure.
	* @param string|null $host = DEDALO_HOSTNAME_CONN
	* @param string $user = DEDALO_USERNAME_CONN
	* @param string $password = DEDALO_PASSWORD_CONN
	* @param string $database = DEDALO_DATABASE_CONN
	* @param string|int|null $port = DEDALO_DB_PORT_CONN
	* @param string|null $socket = DEDALO_SOCKET_CONN
	* @param bool $cache = true
	* @return PgSql\Connection|bool $pg_conn
	* 	>=8.1.0	Returns an PgSql\Connection instance now; previously, a resource was returned.
	* 	false on failure
	*/
	public static function _getConnection(
		string|null		$host		= DEDALO_HOSTNAME_CONN,
		string			$user		= DEDALO_USERNAME_CONN,
		string			$password	= DEDALO_PASSWORD_CONN,
		string			$database	= DEDALO_DATABASE_CONN,
		string|int|null	$port		= DEDALO_DB_PORT_CONN,
		string|null		$socket		= DEDALO_SOCKET_CONN,
		bool			$cache		= true
		) : PgSql\Connection|bool {

		static $pg_conn;
		if($cache===true && isset($pg_conn)) {
			return($pg_conn);
		}

		// basic str_connect with mandatory vars
		$str_connect = "dbname=$database user=$user password=$password";

		// Port is optional
		if(!empty($port)) {
			$str_connect = 'port=' . (int)$port .' '.$str_connect;
		}

		// Host is optional. When false, use default socket connection
		if($host!==null) {
			$str_connect = "host=$host ".$str_connect;
		}

		// Connecting, selecting database
		$pg_conn_real = pg_connect($str_connect);
		if($pg_conn===false) {
			debug_log(__METHOD__
				.' Error. Could not connect to database (52) : '.to_string($database)
				, logger::ERROR
			);
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
	* @return PgSql\Connection $pg_conn
	* 	>=8.1.0	Returns an PgSql\Connection instance now; previously, a resource was returned.
	* 	false on failure
	*/
	public static function _getNewConnection(
		string|null		$host		= DEDALO_HOSTNAME_CONN,
		string			$user		= DEDALO_USERNAME_CONN,
		string			$password	= DEDALO_PASSWORD_CONN,
		string			$database	= DEDALO_DATABASE_CONN,
		string|int|null	$port		= DEDALO_DB_PORT_CONN,
		string|null		$socket		= DEDALO_SOCKET_CONN
		) : PgSql\Connection|bool {

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
	* Returns an PosgreSQL PDO instance on success, or false on failure.
	* @param string|null $host = DEDALO_HOSTNAME_CONN
	* @param string $user = DEDALO_USERNAME_CONN
	* @param string $password = DEDALO_PASSWORD_CONN
	* @param string $database = DEDALO_DATABASE_CONN
	* @param string|int|null $port = DEDALO_DB_PORT_CONN
	* @param string|null $socket = DEDALO_SOCKET_CONN
	* @param bool $cache = true
	* @return PDO|bool $pg_pdo_conn
	*/
	public static function _getConnectionPDO(
		string|null		$host		= DEDALO_HOSTNAME_CONN,
		string			$user		= DEDALO_USERNAME_CONN,
		string			$password	= DEDALO_PASSWORD_CONN,
		string			$database	= DEDALO_DATABASE_CONN,
		string|int|null	$port		= DEDALO_DB_PORT_CONN,
		string|null		$socket		= DEDALO_SOCKET_CONN,
		bool			$cache		= true
		) : PDO|bool {

		static $pdo_conn;
		if($cache===true && isset($pdo_conn)) {
			return($pdo_conn);
		}

		// PDO
			try {
				$pdo_conn = new PDO(
					'pgsql:host=' . $host . ';dbname=' . $database . ';', $user, $password, array(
						PDO::ATTR_ERRMODE =>  PDO::ERRMODE_EXCEPTION,
					)
				);
			} catch (\PDOException $e) {
				throw new \PDOException($e->getMessage(), (int)$e->getCode());
			}

		return $pdo_conn;
	}//end _getConnectionPDO



	/**
	* _GETCONNECTION_MYSQL
	* Returns an mysqli instance on success, or false on failure.
	* @param string|null $host = MYSQL_DEDALO_HOSTNAME_CONN
	* @param string $user = MYSQL_DEDALO_USERNAME_CONN
	* @param string $password = MYSQL_DEDALO_PASSWORD_CONN
	* @param string $database = MYSQL_DEDALO_DATABASE_CONN
	* @param string|int|null $port = MYSQL_DEDALO_DB_PORT_CONN
	* @param string|null $socket = MYSQL_DEDALO_SOCKET_CONN
	* @param bool $cache = true
	* @return mysqli|bool $mysqli
	*/
	public static function _getConnection_mysql(
		string|null		$host		= MYSQL_DEDALO_HOSTNAME_CONN,
		string			$user		= MYSQL_DEDALO_USERNAME_CONN,
		string			$password	= MYSQL_DEDALO_PASSWORD_CONN,
		string			$database	= MYSQL_DEDALO_DATABASE_CONN,
		string|int|null	$port		= MYSQL_DEDALO_DB_PORT_CONN,
		string|null		$socket		= MYSQL_DEDALO_SOCKET_CONN,
		bool			$cache		= true
		) : mysqli|bool {

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
			// mysqli_report(MYSQLI_REPORT_ERROR);

		// init
			$mysqli = new mysqli($host, $user, $password, $database, $port);
			if ($mysqli===false) {
				// throw new Exception(' Dedalo '.__METHOD__ . ' Failed mysqli_init ', 1);
				debug_log(__METHOD__
					. " Error on connect to MYSQL database. Failed mysqli_init ". PHP_EOL
					, logger::DEBUG
				);
				return false;
			}
			if ($mysqli->connect_errno) {
			    debug_log(__METHOD__
					. " Error on connect to MYSQL database [2]. ". PHP_EOL
					. ' connect_error: ' . $mysqli->connect_error
					, logger::DEBUG
				);
				return false;
			}

		// $mysqli->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);

		// auto-commit : set autocommit (needed for INNODB save)
			if (!$mysqli->options(MYSQLI_INIT_COMMAND, 'SET AUTOCOMMIT = 1')) {
				// die('Dedalo '.'Setting MYSQLI_INIT_COMMAND failed');
				// throw new Exception(' Connect Error. Setting MYSQLI_INIT_COMMAND failed ', 1);
				debug_log(__METHOD__
					. " Error on connect to MYSQL database [3].  Setting MYSQLI_INIT_COMMAND failed". PHP_EOL
					. 'connect_error: ' . $mysqli->connect_error
					, logger::DEBUG
				);
			}

		// timeout : set connect_timeout
			if (!$mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10)) {
				// die('Dedalo '.'Setting MYSQLI_OPT_CONNECT_TIMEOUT failed');
				// throw new Exception(' Connect Error. Setting MYSQLI_OPT_CONNECT_TIMEOUT failed ', 1);
				debug_log(__METHOD__
					. " Error on connect to MYSQL database [4].  Setting MYSQLI_OPT_CONNECT_TIMEOUT failed". PHP_EOL
					. 'connect_error: ' . $mysqli->connect_error
					, logger::DEBUG
				);
			}

		// connect
			if (!$mysqli->real_connect($host, $user, $password, $database,  $port, $socket)) {
				debug_log(__METHOD__
					. " Error on connect to MYSQL database ". PHP_EOL
					. ' mysqli_connect_errno: ' .mysqli_connect_errno() . PHP_EOL
					. ' mysqli_connect_error: ' .mysqli_connect_error()
					, logger::DEBUG
				);
				return false;
			}

		// UTF8 : Change character set to utf8mb4
			if (!$mysqli->set_charset('utf8mb4')) {
				debug_log(__METHOD__
					." Error loading character set utf8mb4: ". PHP_EOL
					. 'mysqli->error: ' . $mysqli->error
					, logger::DEBUG
				);
			}


		return $mysqli;
	}//end _getConnection_mysql



}//end class DBi
