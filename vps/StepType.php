<?php

class StepType extends ActiveRecord {
	protected $view;
	protected $coach;
	protected $steps;
	
	public function __construct( $attributes = array() ) {
		$this->fields = array( 'id', 'name', 'description', 'view_id', 'coach_id', 'public' );
		$this->table = 'step_types';
		$this->load( $attributes );	
	}
	
	# overloading render and other methods of ViewInterface
	public function __call($name, $arguments) {
		return call_user_func_array( array( $this->getView(), $name ), $arguments );
	}
	
	public function destroy() {
		$result = parent::destroy();
		if( $result ) {
			$ps = new PredefinedSteps();
			$psd = $ps->find( array( 'step_type_id' => $this->getId() ) );
			foreach( $psd as $psde )
				$psde->destroy();
			foreach( $this->getSteps() as $step )
				$step->destroy();
		}
	}
	
	public function getView() {
		if( $this->view == NULL )
			$this->view = new View( $this->attributes['view_id'] );
		return $this->view;
	}
	
	public function getCoach() {
		if( $this->coach == NULL )
			$this->coach = new Coach( $this->attributes['coach_id'] );
		return $this->coach;
	}
	
	public function getSteps() {
		if( $this->steps == NULL ) {
			$step = new Step();
			$this->steps = $step->find( array( 'step_type_id' => $this->getId() ) );
		}
		return $this->steps;
	}
	
	public function validate() {
		if( empty( $this->attributes['name'] ) )
			$this->addError( "Name cannot be empty." );
		$this->attributes['public'] = intval( $this->attributes['public'] );
		if( sizeof( $this->getErrors() ) > 0 )
			return false;
		return true;
	}
}

?>