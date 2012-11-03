<?php
	/**
	 * This file contains the database abstraction class
	 * @package SFATB
	 */
	/**
	 * Database abstraction class
	 *
	 * This is a singleton encapsulating the PDO connection.
	 * Initializing the application loads the PDO and connects
	 * to the server. Then, whenever the PDO connection is
	 * needed, it can be retrieved by <code>$db = DB::instance();</code>
	 * @package SFATB
	 */
	class DB {
		/**
		 * Reference to the only instantiable object of this class
		 * @static
		 * @var DB
		 */
		static $_instance;
		/**
		 * Contains the PDO connection
		 * @var PDO
		 */
		private $dbh;
		
		/**
		 * Overload constructor to disallow using it, effectivly making this a Singleton class.
		 */
		private function __construct() {
		}
		
		/**
		 * Retrieve an instance of DB class possibly creating it on first call
		 * @static
		 * @param string DSN of the database connection if called for the first time
		 * @param string username of the database connection if called for the first time
		 * @param string password of the database connection if called for the first time
		 * @param string PDO driver options for the database connection if called for the first time
		 * @return PDO the PDO connection
		 */
		public static function instance( $dsn = null, $username = null, $password = null, $driver_options = null ) {
			if( self::$_instance == null ) {
				self::$_instance = new DB();
				self::$_instance->setDbh( new PDO( $dsn, $username, $password, $driver_options ) );
			}
			return self::$_instance->getDbh();
		}
		
		/**
		 * Returns the internal PDO connection
		 * @return PDO the PDO connection
		 */
		public function getDbh() {
			return $this->dbh;
		}
		
		/**
		 * Sets the internal PDO connection
		 * @param PDO the PDO connection
		 */
		public function setDbh( $dbh ) {
			$this->dbh = $dbh;
		}
	}
?>