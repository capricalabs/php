<?php

class Template extends ActiveRecord {
	protected $stages;
	protected $coach;
    protected $student;
	
	public function __construct( $attributes = array() ) {
		$this->fields = array( 'id', 'name','description', 'coach_id', 'public', 'student_id' );
		$this->table = 'templates';
		$this->load( $attributes );
	}
	
	public function getStages() {
		if( $this->stages == NULL ) {
			$this->stages = array();
			$db = DataBase::instance();
			$db->DBQuery( "select stages.* from stages left join template_stages on stages.id = template_stages.stage_id where template_stages.template_id = '".$this->getId()."' order by template_stages.position" );
			$rows = $db->fetchAll();
			foreach( $rows as $row ) {
				$stage = new Stage( $row );
				$stage->setTemplate( $this );
				$this->stages[$stage->getId()] = $stage;
			}
		}
		return $this->stages;
	}
	
	public function validate() {
		if( empty( $this->attributes['name'] ) )
			$this->addError( "Name cannot be empty." );
		$this->attributes['public'] = intval( $this->attributes['public'] );
		if( sizeof( $this->getErrors() ) > 0 )
			return false;
		return true;
	}
	
	public function getSteps($scenarioID=false) {
		$steps = array();
		foreach( $this->getStages() as $stage ) {
			$steps = array_merge( $steps, $stage->getSteps($scenarioID) );
		}
		return $steps;
	}
	
	public function getEvaluationSteps() {
		$steps = array();
		foreach( $this->getStages() as $stage ) {
			$steps = array_merge( $steps, $stage->getEvaluationSteps() );
		}
		return $steps;
	}
	
	public function getStepsForSelect() {
		$steps = array('0' => 'No Step');
		foreach( $this->getSteps() as $key => $step ) {
			if( get_class( $step ) != 'Step' )
				continue;
			$steps[$step->getId()] = "Step ".( $key+1 )." :: $step->name";
		}
		return $steps;
	}
	
	public function getStepsForChatConvert() {
		$steps = array('0' => '0');
		foreach( $this->getSteps() as $key => $step ) {
			$steps[$step->getId()] = $key+1;
		}
		return $steps;
	}
	
	public static function fetchPublic( $coach_id = 0 ) {
		$conditions = array( 'public = ?', TRUE );
		if( $coach_id ) {
			$conditions[0] .= ' or coach_id = ?';
			$conditions[] = $coach_id;
		}
		$t = new Template();
		return $t->find( $conditions );
	}
}

?>