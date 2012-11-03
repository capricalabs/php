<?php

class Stage extends ActiveRecord {
	protected $steps;
	protected $evaluation_steps;
	protected $template;
	protected $coach;

	public function __construct( $attributes = array() ) {
		$this->fields = array( 'id', 'name', 'description', 'use_idea_word', 'stage_word', 'use_stage_word', 'coach_id', 'public', 'sequential', 'dynamic', 'show_description' );
		$this->table = 'stages';
		$this->load( $attributes );
	}
	
    public function destroy() {
        $db = DataBase::instance();
        return $db->DBQuery( "update booklets set start_stage = null where start_stage = ".$this->getId() )
            && $db->DBQuery( "delete choices, step_choices from step_choices inner join choices on step_choices.choice_id = choices.id where choices.stage_id = ".$this->getId() )
            && parent::destroy();
    }
    
	public function getSteps($scenarioID=false) {
		if( $this->steps == NULL ) {
            if( empty( $scenarioID)){
                global $SYS_SCENARIO_ID;
                $scenarioID = $SYS_SCENARIO_ID;
            }
			$this->steps = array();
			$db = DataBase::instance();
			$db->DBQuery( "select * from stage_steps where stage_id = '".$this->getId()."' order by position" );
			$rows = $db->fetchAll();
			foreach( $rows as $row ) {
				if( $row['evaluation_step_id'] ) {
					$step = new EvaluationStep( $row['evaluation_step_id'] );
					$step->setStep( end( $this->steps ) );
				} else {
					$step = new Step( $row['step_id'] );
					if($scenarioID){
						$step->updateScenarioLevelMods($scenarioID);
                    }
				}
				$step->setStage( $this );
				$this->steps[] = $step;
			}
		}
		return $this->steps;
	}
	
	public function getStepCount() {
		$db = DataBase::instance();
		$cnt = $db->DBFetchOne("select count(*) cnt from stage_steps where stage_id = " . $this->getId() );
		return $cnt['cnt'];
	}
	
	public function getEvaluationSteps() {
		if( $this->evaluation_steps == NULL ) {
			$steps = $this->getSteps();
			$this->evaluation_steps = array();
			$db = DataBase::instance();
			$db->DBQuery( "select evaluation_steps.* from evaluation_steps left join stage_evaluation_steps on evaluation_steps.id = stage_evaluation_steps.evaluation_step_id where stage_evaluation_steps.stage_id = '".$this->getId()."' order by stage_evaluation_steps.position" );
			$rows = $db->fetchAll();
			$pos = 0;
			foreach( $rows as $row ) {
				$evaluation_step = new EvaluationStep( $row );
				# for now, ignore evaluation steps as part of user model and count normal steps only
				while( $steps[$pos] && get_class( $steps[$pos] ) == 'EvaluationStep' )
					$pos++;
				if( $steps[$pos] )
					$evaluation_step->setStep( $steps[$pos] );
				$pos++;
				$this->evaluation_steps[] = $evaluation_step;
        #echo "$evaluation_step->name -> ".$evaluation_step->getStep()->name."<br/>\n";
			}
		}
		return $this->evaluation_steps;
	}
	
	public function getCoach() {
		if( $this->coach == NULL )
			$this->coach = new Coach( $this->attributes['coach_id'] );
		return $this->coach;
	}
	
	public function validate() {
		if( empty( $this->attributes['name'] ) )
			$this->addError( "Name cannot be empty." );
		# ensure a ranking step is preceeded by 2 discover ideas steps which will be the solutions and criteria to rank
/*
		if( is_array( $this->mnm_records['Steps'] ) ) {
			$current_steps = array_merge( $this->getSteps(), $this->mnm_records['Steps'] );
			$this->steps = null;
			foreach( $current_steps as $key => $step ) {
				if( !is_object( $step ) )
					$step = new Step( $step );
				if( get_class( $step ) == 'Step' && $step->getStepType()->getView()->class_name == 'RankIdeaView' ) {
					$steps = array( $current_steps[$key-1], $current_steps[$key-2] );
					foreach( $steps as $step_id ) {
						$st = new Step( $step_id );
						if( $step_id == 0 || ( $st->getStepType()->getView()->class_name != 'GenerateIdeaView' && $st->getStepType()->getView()->class_name != 'SelectIdeaView' ) ) {
							$this->addError( "$step->name being a ranking step must be preceeded by two steps with view Generate Ideas." );
							break;
						}
					}
				}
			}
		}
*/
		$this->attributes['public'] = intval( $this->attributes['public'] );
		if( sizeof( $this->getErrors() ) > 0 )
			return false;
		return true;
	}
	
	public function setTemplate( $template ) {
		$this->template = $template;
	}
	
	public function getTemplate() {
        if( empty( $this->template)){
            $db = DataBase::instance();
            $template = $db->DBFetchOne( "select template_id from template_stages where stage_id = " . $this->getId() );
            $this->template = new Template( $template[0]);
        }
		return $this->template;
	}
	
	public function getPreviousStage() {
		$previous = null;
		$stages = $this->getTemplate()->getStages();
		foreach( $stages as $stage ) {
			if( $stage->getId() == $this->getId() )
				return $previous;
			$previous = $stage;
		}
	}
	
	public function getNextStage() {
		$next = null;
        foreach( $this->getSteps() as $step){
            if( $step->isSelectStage()){
                return $next;
            }
        }
        
		$stages = array_reverse( $this->getTemplate()->getStages() );
		foreach( $stages as $stage ) {
			if( $stage->getId() == $this->getId() )
				return $next;
			$next = $stage;
		}
	}

	public function isSortable( $relation ) {
		switch( $relation ) {
			case "Steps":
			case "EvaluationSteps":
				return TRUE;
				
			default:
				return parent::isSortable( $relation );
		}
	}
    
    public static function getDynamicStages(){
        $stage = new Stage;
        $stages = array();
        foreach( $stage->find( array('dynamic'=> TRUE),'name') as $stage){
            $stages[$stage->getId()] = $stage->name;
        }
        return $stages;
    }

    public function getHumanName(){
        if( $this->use_stage_word){
            return htmlspecialchars( $this->stage_word );
        }
        
        $stage_order = 0;
        foreach( $this->getTemplate()->getStages() as $stage ) {
            $stage_order++;
            if( $stage->getId() == $this->getId() ){
                break;
            }
        }
        return "Stage $stage_order";
    }
}

?>