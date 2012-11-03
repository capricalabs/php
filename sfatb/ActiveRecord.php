<?php
/**
 * This file contains the database abstraction class
 * @package SFATB
 */
/**
 * Database abstraction class
 *
 * Follows the "Active Record" pattern for access, load, store
 * and manipulation of database records. Every class representing
 * database record must extend ActiveRecord to be able to use
 * its methods and logic.
 * @abstract
 * @package SFATB
 */
abstract class ActiveRecord {
	/**
	 * Store database column names which become fields in the subclasses
	 * @var array
	 */
	protected $fields;
	/**
	 * Associative array storing field names and their values for the particular object
	 * @var array
	 */
	protected $attributes;
	/**
	 * Database column name of the primary key
	 * @var string
	 */
	protected $id = 'id';
	/**
	 * Database table name of the table holding the data for this object
	 * @var string
	 */
	protected $table;
	/**
	 * Store errors connected with validation of this object
	 * @var array
	 */
	private $errors = array();
	
	/**
	 * Overloaded operator for accessing object fields
	 * 
	 * Access and returns the field data by field name stored in
	 * the internal associative array. Uses PHP method overloading
	 * to allow us to call <code>$object->field</code> without
	 * polluting the class with all the "getters".
	 * @param string the requested field name
	 * @return string data stored in this field for this object
	 */
	public function __get( $name ) {
		return $this->attributes[$name];
	}
	
	/**
	 * Overloaded operator for accessing foreign key entities
	 * 
	 * Access and returns the object(s) associated with this
	 * object. Uses PHP method overloading to allow us to call
	 * <code>$object->getAssociated()</code> without polluting
	 * the class with all the "getters".
	 * @param string the requested field name
	 * @param array the arguments passed to the method
	 * @return object the associated object or array of objects
	 */
	public function __call( $name, $arguments ) {
		if( substr( $name, 0, 3 ) == 'get' ) {
			$class = substr( $name, 3 );
			if( $class == AkInflector::pluralize( $class ) ) {
				$field = AkInflector::underscore( $class );
				$class = AkInflector::singularize( $class );                
				if( !$this->$field ) {
					$o = new $class();                     
					$this->$field = $o->find( array( 'conditions' => array( AkInflector::underscore( get_class( $this ) ).'_id' => $this->getId() ) ) );
				}
				return $this->$field;
			} else {
				$field = AkInflector::underscore( $class );
				if( in_array( $field.'_id', $this->fields ) ) {
					if( !$this->$field )
						$this->$field = new $class( $this->attributes[$field.'_id'] );
					return $this->$field;
				}
			}
		}
		$backtrace = debug_backtrace();
		trigger_error("Call to undefined method ".get_class( $this )."::$name() in ".$backtrace[1]['file']." on line ".$backtrace[1]['line'], E_USER_ERROR);
	}
	
	/**
	 * Load object fields with data either from database or from an array
	 * 
	 * This is meant to be executed via the constructor of subclasses
	 * to load the object with data. There are two common usage cases:
	 * <ol>
	 * <li>Load an object by its primary key - field data is taken from database</li>
	 * <li>Load an object with data coming from user - field data is populated from
	 * the passed associative array which usually comes from GET/POST HTML forms</li>
	 * </ol>
	 * @param mixed either the integer primary key of the requested record or associative array of field names and data
	 */
	public function load( $attributes ) {
		$this->attributes = array_fill_keys( $this->fields, NULL );
		if( !is_array( $attributes ) && intval( $attributes ) > 0 ) {
			# load by id
			$db = DB::instance();
			$stmt = $db->prepare( "select * from $this->table where $this->id = ?" );
			$stmt->execute( array( $attributes ) );
			$attributes = $stmt->fetch();
		}                                                                                      
		if( !is_array( $attributes ) ) {
			return false;
		}
		$this->update_attributes( $attributes );
		$this->after_initialize();
	}
	
	/**
	 * Actually modifies the internal storage of fields and data
	 * 
	 * Given an associative array of fields and data this method
	 * stores the fields that match field names in database into
	 * the internal array
	 * @param array associative array of field names and their values
	 */
	public function update_attributes( $attributes ) {
		foreach( $attributes as $field => $value )
			if( in_array( (string)$field, $this->fields ) )
				$this->attributes[$field] = $value;
	}
	
	/**
	 * Checks whether the object is new record or not
	 * 
	 * If object is saved into the database (i.e. has a primary key)
	 * then it is not a new record, otherwise - it is.
	 * @return boolean whether or not the object is new record
	 */
	public function new_record() {
		return $this->attributes[$this->id] == 0;
	}
	
	/**
	 * Saves an object with all its data into the database
	 * 
	 * Called whenever an object must be saved - no matter if it is
	 * modified or not. Calls helper methods to create the object
	 * (i.e. insert it) or update its data into the database.
	 * 
	 * This method also calls the validate method to ensure object
	 * is valid before saving it. Adds an object error when validation
	 * passed but saving fails.
	 * @return boolean result of the save operation
	 */
	public function save() {
		$result = false;
		if( $this->validate() ) {
			if( $this->new_record() )
				$result = $this->create();
			else
				$result = $this->update();
			if( !$result && sizeof( $this->getErrors() ) == 0 )
				$this->addError( "Could not save record." );
		}
		return $result;
	}
	
	/**
	 * Attempts to insert the object as record in database
	 * 
	 * Calls the before_create and after_create hooks. Builds the SQL query and
	 * dispatch it to the database.
	 * @return boolean result of the create operation
	 */
	public function create() {
		if( !$this->before_create() )
			return false;
		$db = DB::instance();
		$sql = "insert into $this->table ( ".implode( ', ', array_keys( $this->attributes ) )." ) values( ".implode( ', ', $this->escaped_attributes() )." )";        
		if( $db->exec( $sql ) ) {
			$this->attributes[$this->id] = $db->lastInsertId();
			$this->after_create();
			return true;
		}
		return false;
	}
	
	/**
	 * Escape internal field data to be used in SQL queries
	 * 
	 * Properly escape field values using the PDO quote method.
	 * @return array escaped field values
	 */
	private function escaped_attributes() {
		$db = DB::instance();
		$values = array();
		foreach( $this->attributes as $val ) {
			if( is_null( $val ) )
				$values[] = 'NULL';
			else
				$values[] = $db->quote( $val );
		}
		return $values;
	}
	
	/**
	 * Attempts to update the object as record in database
	 * 
	 * Calls the before_update and after_update hooks. Builds the SQL query and
	 * dispatch it to the database.
	 * @return boolean result of the update operation
	 */
	public function update() {
		if( !$this->before_update() )
			return false;
		$db = DB::instance();
		$sql = "update $this->table set ";
		foreach( $this->attributes as $field => $value ) {
			$sql .= " $field = ";
			if( is_null( $value ) )
				$sql .= 'NULL';
			else
				$sql .= $db->quote( $value );
			$sql .= ", ";
		}
		$sql = substr( $sql, 0, -2 );
		$sql .= " where $this->id = ".$this->getId();
		$result = $db->exec( $sql );
		$this->after_update();
		return $result !== false;
	}
	
	/**
	 * Attempts to delete the object as record in database
	 * 
	 * Builds the SQL query and dispatch it to the database.
	 * Adds an object error on failure.
	 * @return boolean result of the destroy operation
	 */
	public function destroy() {
		$db = DB::instance();
		$res = $db->exec( "delete from $this->table where $this->id = ".$this->getId() );
		if( $res === false )
			$this->addError( "Could not delete record." );
		return $res;
	}
	
	/**
	 * Hook method called after an object is loaded with data
	 * 
	 * Can be used for setting default fields or performing
	 * special tasks on object instantiation.
	 */
	public function after_initialize() {}
	/**
	 * Hook method called before creating an object in database
	 * 
	 * Can be used for special validation or default fields.
	 * @return boolean whether or not the special validation passes
	 */
	public function before_create() { return true; }
	/**
	 * Hook method called after creating an object in database
	 * 
	 * Can be used for performing special tasks after an object is
	 * created - like sending emails, creating other objects, etc.
	 */
	public function after_create() {}
	/**
	 * Hook method called before updating an object in database
	 * 
	 * Can be used for special validation or default fields.
	 * @return boolean whether or not the special validation passes
	 */
	public function before_update() { return true; }
	/**
	 * Hook method called after updating an object in database
	 * 
	 * Can be used for performing special tasks after an object is
	 * changed - like sending emails, updating other objects, etc.
	 */
	public function after_update() {}
	
	/**
	 * Perform validation on object field data
	 * 
	 * This method is meant to be overloaded in subclasses to perform
	 * data validation - format, integrity, constraints, etc. - before
	 * saving the object in database.
	 * @return boolean result of the validation
	 */
	public function validate() {
		return true;
	}
	
	/**
	 * Adds an error message to the internal storage of error messages
	 * 
	 * Validation functions usually call this method to add object
	 * error messages which are later displayed to the user.
	 * @param string the error message
	 */
	public function addError( $msg ) {
		$this->errors[] = $msg;
	}
	
	/**
	 * Returns all the object error messages
	 * 
	 * @return array the error messages
	 */
	public function getErrors() {
		return $this->errors;
	}
	
	/**
	 * Returns the primary key value of this object
	 * 
	 * @return int the primary key value (id)
	 */
	public function getId() {
		return intval( $this->attributes[$this->id] );
	}
	
	/**
	 * Finder method to search and retrieve records from database
	 * 
	 * Dynamically builds an SQL query to retrieve specific records
	 * from database. Accepted params array has the following structure:
	 * <ul>
	 * <li>conditions - associative array of field names and values OR
	 * array containing the WHERE part of SQL query with placeholders
	 * in index 0 and the rest of the elements containing the data which
	 * should replace the placeholders. Example:
	 * <code>array( 'name = ? and age = ?', 'Tom', 32 )</code>
	 * </li>
	 * <li>join - the join part of the SQL query where a join is
	 * necessary to retrieve the records</li>
	 * <li>orderby - field name of the column or columns by which
	 * to order the results; if it contains a foreign key ending in '_id'
	 * this method translates this to the foreign table and column name</li>
	 * <li>orderdir - asc/desc for ascending or descending order</li>
	 * <li>limit - MySQL limit clause, e.g. 0,30 - meaning start from record
	 * 0 and return 30 results
	 * </ul>
	 * @param array the parameters for the search operation
	 * @return array array of objects instantiated from the found records in DB
	 */
	public function find( $params = array() ) {
		$records = array();
		$class = get_class( $this );
		$db = DB::instance();
		$where = "1";
		if( is_array( $params['conditions'] ) ) {
			if( $params['conditions'][0] ) {
				$where = $params['conditions'][0];
				for( $i = 1; $i < sizeof( $params['conditions'] ); $i++ )
					$where = preg_replace( '/\?/', $db->quote( $params['conditions'][$i] ), $where, 1 );
			} else
				foreach( $params['conditions'] as $field => $value ) {
					if( in_array( $field, $this->fields ) )
						$where .= " and $this->table.$field = ".$db->quote( $value );
				}
		}
		if( !in_array( $params['orderby'], $this->fields ) && !strstr( $params['orderby'], '.' ) )
			$params['orderby'] = $this->id;
		if( $params['orderdir'] != 'asc' && $params['orderdir'] != 'desc' )
			$params['orderdir'] = 'asc';
		# handle order by foreign key
		if( preg_match( '/^(.*)_id$/', $params['orderby'], $match ) ) {
			$join_table = $match[1]."s";
			$params['join'] .= " left join $join_table on $this->table.$params[orderby] = $join_table.id";
			$params['orderby'] = "$join_table.name";
		}
		if( !strstr( $params['orderby'], '.' ) )
			$params['orderby'] = "$this->table.$params[orderby]";
		if( $params['orderby_full'] ) {
			$params['orderby'] = $params['orderby_full'];
			$params['orderdir'] = '';
		}
		if( $params['limit'] )
			$params['limit'] = "limit $params[limit]";
		$stmt = $db->query( "select $this->table.* from $this->table $params[join] where $where order by $params[orderby] $params[orderdir] $params[limit]" );
		$records = $stmt->fetchAll();
		foreach( $records as $key => $row ) {
			$records[$key] = new $class( $row );
		}
		return $records;
	}
	
	/**
	 * Finds and returns the number of records meeting certain criteria
	 * 
	 * Uses the conditions and join clauses to filter the possible results
	 * and returns the number of found records
	 * @see find
	 * @return int the number of records matching the criteria
	 */
	public function count( $conditions = array(), $join = null ) {
		$class = get_class( $this );
		$db = DB::instance();
		$where = "1";
		if( is_array( $conditions ) ) {
			if( $conditions[0] ) {
				$where = $conditions[0];
				for( $i = 1; $i < sizeof( $conditions ); $i++ )
					$where = preg_replace( '/\?/', $db->quote( $conditions[$i] ), $where, 1 );
			} else
				foreach( $conditions as $field => $value ) {
					if( in_array( $field, $this->fields ) )
						$where .= " and $this->table.$field = ".$db->quote( $value );
				}
		}
		$stmt = $db->query( "select count(distinct $this->table.$this->id) as cnt from $this->table $join where $where" );
		$record = $stmt->fetch();
		return $record['cnt'];
	}
	
	/**
	 * Returns all object fields and data
	 * 
	 * @return array the associative array of object fields and their values stored internally in this object
	 */
	public function getAttributes() {
		return $this->attributes;
	}
}

?>