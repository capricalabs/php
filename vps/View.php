<?php

class View extends ActiveRecord {
	private $presentation;
	
	public function __construct( $attributes = array() ) {
		$this->fields = array( 'id', 'name', 'class_name' );
		$this->table = 'views';
		$this->load( $attributes );
	}
	
	# overloading render and other methods of ViewInterface
	public function __call($name, $arguments) {
		$callee = array( $this->getPresentation(), $name );
		if( substr( $name, 0, 2 ) == "is" && !is_callable( $callee ) ) {
			return FALSE; // no such checker defined
		}
        if( $name == "render"){
#ini_set('display_errors',"1"); error_reporting( E_NOTICE);
            //echo "<!-- View class: $this->class_name -->";
        }
		return call_user_func_array( $callee, $arguments );
	}
	
	public function getPresentation() {
		if( $this->presentation == NULL && !empty( $this->attributes['class_name']))
			$this->presentation = new $this->attributes['class_name'];
		return $this->presentation;
	}
	
	public function validate() {
		if( empty( $this->attributes['name'] ) )
			$this->addError( "Name cannot be empty." );
		if( !class_exists( $this->attributes['class_name'] ) )
			$this->addError( "Invalid view class selected." );
		if( sizeof( $this->getErrors() ) > 0 )
			return false;
		return true;
	}
}

?>