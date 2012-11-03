<?php

abstract class ActiveRecord {
	protected $fields;
	protected $attributes;
	protected $id = 'id';
	protected $table;
	protected $mnm_records = array();
	private $errors = array();
    static $tables = array(); // schema cache
	
	public function __get( $name ) {
		return $this->attributes[$name];
	}
	 
	public function __call( $name, $arguments ) {
		if( substr( $name, 0, 3 ) == 'get' ) {
			$class = substr( $name, 3 );
			if( $class == AkInflector::pluralize( $class ) ) {
				$field = AkInflector::underscore( $class );
				$class = AkInflector::singularize( $class );
				if( !$this->$field ) {
					$o = new $class();
					$this->$field = $o->find( array( AkInflector::underscore( get_class( $this ) ).'_id' => $this->getId() ) );
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

	public function load( $attributes ) {
		$this->attributes = array_fill_keys( $this->fields, NULL );
		if( !is_array( $attributes ) && intval( $attributes ) > 0 ) {
			// load by id
			$db = DataBase::instance();			
			$attributes = $db->DBFetchOne( "select * from $this->table where $this->id = ".intval( $attributes ) );
		}
		if( !is_array( $attributes ) ) {
			return false;
		}
		$this->update_attributes( $attributes );
		$this->after_initialize();
	}
	
	public function update_attributes( $attributes ) {
		$mnm_relations = $this->getManyToManyRelations();
        if( $this->trace){
            echo "Relations: ";
            print_r( $mnm_relations);
        }
		foreach( $attributes as $field => $value ) {
			if( in_array( (string)$field, $this->fields ) ) {
				$this->attributes[$field] = $value;
			} elseif( in_array( (string)$field, $mnm_relations ) ) {
				# store many to many relations for possbile saving
				$this->mnm_records[$field] = $value;
                if( $this->trace){
                    print_r( $this->mnm_records);
                }
			} elseif( !is_numeric( $field ) ) {
				#echo "Unknown $field";
			}
		}
	}
	
	public function new_record() {
		return $this->attributes[$this->id] == 0;
	}
	
	public function save() {
		$result = false;
		if( $this->validate() ) {
			if( $this->new_record() ) {
				$result = $this->create();
			} else {
				$result = $this->update();
            }

            if( $this->trace){
                echo "Relation records before save: ";
                print_r( $this->mnm_records);
            }
			if( $result && sizeof( $this->mnm_records ) > 0 ) {
				$this->saveMNM();
			}
		}
		return $result;
	}
	
	public function create() {
		if( !$this->before_create() )
			return false;
		$db = DataBase::instance();
		if( $db->DBInsert( $this->table, $this->attributes ) ) {
			$this->attributes[$this->id] = $db->DBLastInsertedRow();
			$this->after_create();
			return true;
		}
        $this->addError( $db->getErrorMessage());
		return false;
	}
	
	public function update() {
		if( !$this->before_update() )
			return false;
		$db = DataBase::instance();
		$result = $db->DBUpdate( $this->table, $this->attributes, $this->id.' = '.$this->getId() );
		if( $result ) {
			$this->after_update();
        } else {
            $this->addError( $db->getErrorMessage());
        }
		return $result;
	}
	
	public function destroy() {
		$db = DataBase::instance();
		if( !$db->DBQuery( "delete from $this->table where $this->id = ".$this->getId() )){
			$this->addError( $db->getErrorMessage());
			return false;
		} else {
			$relations = $this->getManyToManyRelations();
			foreach( $relations as $rel ) {
				if( $rel == 'PreviousStepsToDisplay' ) {
					$db->DBQuery( "delete from previous_steps_to_display where step_id = '".$this->getId()."'" );
					continue;
				}
				$table = AkInflector::singularize( $this->table ).'_'.AkInflector::underscore( AkInflector::pluralize( $rel ) );
				$fk = AkInflector::singularize( $this->table ).'_id';
				$db->DBQuery( "delete from $table where $fk = '".$this->getId()."'" );
			}
		}
		return true;
	}
	
	public function saveMNM() {
		# save many to many records if any
		$db = DataBase::instance();
		foreach( $this->mnm_records as $relation => $records ) {
			if( is_array( $records ) )
			foreach( $records as $id ) {
				$key1 = AkInflector::foreignKey( get_class( $this ) );
				if( preg_match( '/^([^\d]+)(\d+)$/', $id, $m ) ) {
					$id = $m[2];
					$key2 = $m[1]."_id";
				} else
					$key2 = AkInflector::singularize( AkInflector::underscore( $relation ) )."_id";
				$table = AkInflector::underscore( get_class( $this ) )."_".AkInflector::underscore( $relation );
				$pos = $db->DBFetchOne( "select max( position ) as pos from $table where $key1 = '".$this->getId()."'" );
				$pos = intval( $pos['pos'] ) + 1;
				$record = array( $key1 => $this->getId(), $key2 => $id, 'position' => $pos );
                if( $this->trace){
                    echo "Add relation record: ";
                    print_r( $record);
                }
				if( $db->DBFetchOne( "select * from $table where $key1 = '".$this->getId()."' and $key2 = '$id'" ) ) {
                    $this->addError("This " . get_class( $this ) . " already contains this " . AkInflector::singularize($relation));
				}
				else {
					$db->DBInsert( $table, $record );
				}
			}
		}
		$this->mnm_records = array();
	}
	
	public function deleteMNM( $relation, $id, $type = '' ) {
		$db = DataBase::instance();
		$key1 = AkInflector::foreignKey( get_class( $this ) );
		if( $type )
			$key2 = AkInflector::singularize( AkInflector::underscore( $type ) )."_id";
		else
			$key2 = AkInflector::singularize( AkInflector::underscore( $relation ) )."_id";
		$table = AkInflector::underscore( get_class( $this ) )."_".AkInflector::underscore( $relation );
		$rel = $db->DBFetchOne( "select * from $table where $key1 = '".$this->getId()."' and $key2 = '".intval( $id )."'" );
		$db->DBQuery( "delete from $table where $key1 = '".$this->getId()."' and $key2 = '".intval( $id )."'" );
		$db->DBQuery( "update $table set position=position-1 where $key1 = '".$this->getId()."' and position > $rel[position]" );
	}
	
	public function getMNM( $relation ) {
		return $this->mnm_records[$relation];
	}
	
	public function after_initialize() {}
	public function before_create() { return true; }
	public function after_create() {}
	public function before_update() { return true; }
	public function after_update() {}
	
	public function copy() {
		$attribs = $this->getAttributes();
		$attribs['id'] = null;
		$mnm_relations = $this->getManyToManyRelations();
		foreach( $mnm_relations as $rel ) {
			$method = "get$rel";
			$records = $this->$method();
			foreach( $records as $record ) {
				if( $rel == 'PreviousStepsToDisplay' )
					$attribs[$rel][] = $record->previous_step_id;
				elseif( $rel == 'Steps' && get_class( $record ) == 'EvaluationStep' )
					$attribs[$rel][] = "evaluation_step".$record->getId();
				else
					$attribs[$rel][] = $record->getId();
			}
		}
		$class = get_class( $this );
		$record = new $class;
		$record->update_attributes( $attribs );
		$record->save();
		return $record;
	}
	
	public function validate() {
		return true;
	}
	
	public function addError( $msg ) {
		$this->errors[] = $msg;
	}
	
	public function getErrors() {
		return $this->errors;
	}
	
	public function getId() {
		return intval( $this->attributes[$this->id] );
	}
	
	public function find( $conditions = array(), $orderby = null, $orderdir = null, $limit = null, $join = null ) {
		$records = array();
		$class = get_class( $this );
		$db = DataBase::instance();
		$where = "1";
		if( is_array( $conditions ) ) {
			if( $conditions[0] ) {
				$where = $conditions[0];
				for( $i = 1; $i < sizeof( $conditions ); $i++ )
					$where = preg_replace( '/\?/', "'".$db->DBEscape( $conditions[$i] )."'", $where, 1 );
			} else
				foreach( $conditions as $field => $value ) {
					if( in_array( $field, $this->fields ) )
						$where .= " and $this->table.$field = '".$db->DBEscape( $value )."'";
				}
		}
                 else if(is_string($conditions) ) $where = $conditions;
		/* allow sorting by a related field - see Field class
		if( !in_array( $orderby, $this->fields ) )
			$orderby = $this->id;
		*/
		# handle order by foreign key
		if( !$join )
			$join = "";
		if( !$orderby ) {
			$orderby = $this->id;
		} else {
			$orderby = str_replace( "`", "", $orderby );
		}

		if( preg_match( '/^(.*)_id$/', $orderby, $match ) && !strstr( $orderby, '.' ) ) {
			$join_table = AkInflector::pluralize( $match[1] );
			$join .= " left join $join_table on $this->table.$orderby = $join_table.id";
			$orderby = "$join_table.name";
		}
		
        $orderdir = strtolower( $orderdir);
		if( $orderdir != 'asc' && $orderdir != 'desc' )
			$orderdir = FALSE;
		
		$order = array();
		foreach( preg_split( '/\s*,\s*/', $orderby ) as $field )
			if( !strstr( $field, '.' ) ) {
				$order[] = "$this->table.`$field` $orderdir";
			} else {
				$order[] = str_replace(".",".`", $field ) . "` $orderdir";
			}
		$orderby = implode( ',', $order );
		
		$sql = "select $this->table.* from $this->table $join where $where order by $orderby";
		if( $limit )
			$sql .= " limit $limit";

        if( !empty( $this->trace)){
            echo $sql;
        }
		$db->DBQuery( $sql );
		$records = $db->fetchAll();
		foreach( $records as $key => $row ) {
			$records[$key] = new $class( $row );
		}
		return $records;
	}
	
	public function count( $conditions = array(), $join = null ) {
		$class = get_class( $this );
		$db = DataBase::instance();
		$where = "1";
		if( is_array( $conditions ) ) {
			if( $conditions[0] ) {
				$where = $conditions[0];
				for( $i = 1; $i < sizeof( $conditions ); $i++ )
					$where = preg_replace( '/\?/', "'".$db->DBEscape( $conditions[$i] )."'", $where, 1 );
			} else
				foreach( $conditions as $field => $value ) {
					if( in_array( $field, $this->fields ) )
						$where .= " and $this->table.$field = '".$db->DBEscape( $value )."'";
				}
		}
                else if(is_string($conditions) ) $where = $conditions;
                
        $sql = "select count(distinct $this->table.id) as cnt from $this->table $join where $where";
        if( !empty( $this->trace)){
            echo $sql;
        }
		$record = $db->DBFetchOne( $sql );
		return $record['cnt'];
	}
	
	public function getAttributes() {
		return $this->attributes;
	}
	
	public function getObjectByForeignKey( $field ) {
		if( preg_match( "/_id$/", $field ) ) {
			$method = "get".AkInflector::camelize( preg_replace( "/_id$/", "", $field ) );
			$obj = $this->$method();
			return $obj;
		}
		return NULL;
	}
	
	public function getManyToManyRelations() {
		$relations = array();
		$db = DataBase::instance();
		$tbl_prefix = AkInflector::underscore( get_class( $this ) )."_";
        if( empty( self::$tables)){ // only once per request
		    $db->DBQuery( "show tables" );
		    while( $row = $db->DBFetchRow() )
			    self::$tables[] = $row[0];
        }

		foreach( self::$tables as $table ) {
			if( preg_match( "/^$tbl_prefix(.*)$/", $table, $match ) && in_array( $match[1], self::$tables ) ) {
				$relations[] = AkInflector::camelize( $match[1] );
			}
		}

		return $relations;
	}
	
	public function isSortable( $relation ) {
		return FALSE;
	}

	public function move( $relation, $id, $newPos, $type = '' ) {
		$db = DataBase::instance();
		$key1 = AkInflector::foreignKey( get_class( $this ) );
		if( $type )
			$key2 = AkInflector::singularize( AkInflector::underscore( $type ) )."_id";
		else
			$key2 = AkInflector::singularize( AkInflector::underscore( $relation ) )."_id";
		$table = AkInflector::underscore( get_class( $this ) )."_".AkInflector::underscore( $relation );
		$neighbour = $db->DBFetchOne( "select * from $table where $key1 = '".$this->getId()."' and position = $newPos" );
		if( $neighbour ) { // not the first/last record
			$rel = $db->DBFetchOne( "select * from $table where $key1 = '".$this->getId()."' and $key2 = $id" );
			$oldPos = $rel['position'];
            if( $this->trace){
                echo "Move from $oldPos to $newPos";
            }
			$db->DBQuery( "update $table set position = $oldPos where $key1 = '".$this->getId()."' and position = $newPos" );
			$db->DBQuery( "update $table set position = $newPos where $key1 = '".$this->getId()."' and $key2 = $id" );
		} elseif( $this->trace){
            echo "No move to $newPos";
        }
	}

	public function getEditorHTML( $record) {
		ob_start();
		$this->showEditor( $record);
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}
	
	/*
		Override to return a custom HTML code for the editor of this object
		The default here shows a combo with the available records
		Add [] after every field name to cope with list editors
	*/
	protected function showEditor( $record) {
		global $coach;
		if( $coach )
			$objects = $this->find( array( 'coach_id = ? or public', $coach->getId() ), 'name' );
		else
			$objects = $this->find( null, 'name' );
?>
		<select name="<?=AkInflector::pluralize( get_class( $this ) )?>[]">
<?
		foreach( $objects as $obj ) { ?>
			<option value="<?=$obj->getId()?>"><?=htmlspecialchars( $obj->name )?>:<?=$obj->getId()?></option>
<? 		} ?>
		</select>
<?
	}

	/*
	Override to TRUE to show the editor in-place instead in a new page
	*/
	public function isInPlaceEditor() {
		return FALSE;
	}
	
	public function getEditClass() {
		return get_class( $this );
	}
	
	public function isEditLink() {
		return TRUE;
	}
}

?>
