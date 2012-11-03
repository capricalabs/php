<?php

class Step extends ActiveRecord {
	protected $step_type;
	protected $stage;
	protected $judge_step;
	protected $step_fields;
    protected $step_choices;
	protected $specific_fields;
	protected $previous_steps;
	protected $coach;
  protected $questions;
	
	public function __construct( $attributes = array() ) {
		
		$this->fields = array(
			'id',
			'name',
			'summary',
			'description',
			'step_type_id',
			'grid_columns',
			'grid_favorites',
			'show_similar_projects',
			'needed_ideas',
			'unlimited_ideas',
			'idea_word',
      'step_title',
      'description_word',
			'items_per_page',
			'multiline_title',
			'show_description',
			'can_have_predefined_info',
			'rank_type', 'version', 'text_entry', 'dialog_type',
			'criteria_offset', 'solutions_offset', 'rank_offset',
			'use_like_feature', 'use_like_unique', 'top_ideas',
			'individual_rank',
			'coach_id',
			'public',
			'author_exclusive',
      'author_anonymous',
      'show_stage_in_step',
			'examples',
      'dynamic',
      'canvas_type','canvas',
      'newIdeaPosition',
      'max_title_length',
      'subject_for_auto_evaluation',
      'min_tags'
		);
		$this->table = 'steps';
		$this->load( $attributes );
	}
	
	public function __call($name, $arguments) {
		if( $name == 'getStepFiles'){
			return parent::__call($name, $arguments);
		} else { # overloading render and other methods of ViewInterface
			return call_user_func_array( array( $this->getStepType(), $name ), array_merge( array( $this ), $arguments ) );
		}
	}
	
	public function before_update() {
		$this->fix_grid_fields();
		return true;
	}
	
	public function before_create() {
		$this->fix_grid_fields();
		return true;
	}
	
	public function fix_grid_fields() {
		if( $this->is_grid_step() ) {
			$this->attributes['needed_ideas'] = sizeof( explode( ',', $this->grid_columns ) );
			$this->attributes['version'] = 'table';
		}
	}
	
	public function is_grid_step() {
		return $this->getStepType()->getView()->class_name == 'GridView';
	}
	
	public function destroy() {
        foreach( $this->getStepFiles() as $sf ){
            $sf->destroy();
        }

        if( $js = $this->getJudgeStep() ) {
            $js->destroy();
        }

        $db = DataBase::instance();
        return $db->DBQuery( "delete fields from step_fields inner join fields on step_fields.fields_id = fields.id where step_fields.step_id = ".$this->getId() )
		    && parent::destroy();
	}
	
	# implement scenario step start/end times
	public function isActiveStep( $scenario_id ) {
		list( $start_time, $end_time ) = $this->scenarioStartEndTimes( $scenario_id );
		if( $start_time && $end_time ) {
			if( strtotime( $start_time ) > time() || strtotime( $end_time ) < time() )
				return false;
		}
		return true;
	}
	
	public function scenarioStartEndTimes( $scenario_id ) {
		$mod = new ScenarioStepMods();
		$mod = $mod->find( array( 'step_id' => $this->getId(), 'scenario_id' =>$scenario_id ) );
		$mod = $mod[0];
		if( !$mod )
			return array( null, null );
		else
			return array( $mod->start_time, $mod->end_time );
	}
	
	public function updateScenarioLevelMods($scenarioID) {
		
		$step_id = $this->getId();
		$mod = new ScenarioStepMods();
		$mod = $mod->find( array( 'step_id' => $step_id, 'scenario_id' =>$scenarioID, 'time_data_only' => false ) );
		$mod = $mod[0];
		if($mod) {  // NULL means to keep the original value
                if( $mod->name) $this->attributes['name'] = $mod->name;
                $this->attributes['summary'] = $mod->summary;
				$this->attributes['description'] = $mod->description;
				$this->attributes['needed_ideas'] = $mod->needed_ideas;
				$this->attributes['unlimited_ideas'] = $mod->unlimited_ideas;
				$this->attributes['idea_word'] = $mod->idea_word;
                if( $mod->description_word) $this->attributes['description_word'] = $mod->description_word;
				$this->attributes['items_per_page'] = $mod->items_per_page;
				$this->attributes['can_have_predefined_info'] = $mod->can_have_predefined_info;
                if( is_numeric( $mod->multiline_title)) $this->attributes['multiline_title'] = $mod->multiline_title;
                if( $mod->version) $this->attributes['version'] = $mod->version;
                if( $mod->text_entry) $this->attributes['text_entry'] = $mod->text_entry;
                if( $mod->dialog_type) $this->attributes['dialog_type'] = $mod->dialog_type;
                if( is_numeric( $mod->author_exclusive)) $this->attributes['author_exclusive'] = $mod->author_exclusive;
                $this->attributes['hidden'] = $mod->hidden;
		} 
	}
	
	
	public function getStepType() {
		if( $this->step_type == NULL )
			$this->step_type = new StepType( $this->attributes['step_type_id'] );
		
		return $this->step_type;
	}
	
	public function validate() {
		if( empty( $this->attributes['name'] ) )
			$this->addError( "Name cannot be empty." );
		if( $this->attributes['needed_ideas'] == 0  && $this->attributes['unlimited_ideas'] == 0 )
			$this->addError( "Needed Ideas must be greater than zero or set to unlimited." );
		if( empty( $this->attributes['idea_word'] ) )
			$this->addError( "Idea Word cannot be empty." );
		$this->attributes['public'] = intval( $this->attributes['public'] );
		if( sizeof( $this->getErrors() ) > 0 )
			return false;
		return true;
	}
	
	public function setStage( $stage ) {
		$this->stage = $stage;
	}
	
	public function getStage( $booklet_id = null) {
        if( empty( $this->stage)){
            if( $booklet_id){
                $booklet = new Booklet( $booklet_id);
            } else {
                $SYS_BOOKLET_CODE = $_COOKIE[ SYS_SSAVE_COOKIE ];
                if( empty( $SYS_BOOKLET_CODE)){
                    return null;
                }
                $booklet = Booklet::getByCode( $SYS_BOOKLET_CODE );
            }
            $sys_scenario = $booklet->getScenario();
            $template = $sys_scenario->getTemplate();
            foreach( $template->getSteps() as $step){
                if( $step->getId() == $this->getId()){
                    $this->setStage( $step->getStage());
                    break;
                }
            }
        }
		return $this->stage;
	}
	
	public function getCoach() {
		if( $this->coach == NULL )
			$this->coach = new Coach( $this->attributes['coach_id'] );
		return $this->coach;
	}

	public function getOrder( $booklet_id = null) {
		if( !$this->getStage( $booklet_id) )
			return '';
		$steps = $this->getStage()->getTemplate()->getSteps();
		foreach( $steps as $key => $step )
			if( $step->getId() == $this->getId() )
				return $key+1;
		return 0;
	}
	
	# get an associated presentation/judge step by foreign key
	public function getJudgeStep() {
		if( !$this->judge_step ) {
			$js = new JudgeStep();
			$steps = $js->find( array( 'step_id' => $this->getId() ) );
			if( $steps )
				$this->judge_step = $steps[0];
		}
		return $this->judge_step;
	}
	
	public function getPreviousStepsToDisplay() {
		if( $this->previous_steps == null ) {
			$ps = new PreviousStepsToDisplay();
			$this->previous_steps = $ps->find( array( 'step_id' => $this->getId() ) );
		}
		return $this->previous_steps;
	}
	
	# overload this method to properly parse PreviousStepsToDisplay assignment
	public function saveMNM() {
		if( $this->mnm_records['PreviousStepsToDisplay'] ) {
			foreach( $this->mnm_records['PreviousStepsToDisplay'] as $id ) {
				$pstd = new PreviousStepsToDisplay( array( 'step_id' => $this->getId(), 'previous_step_id' => $id ) );
				if( !$pstd->find( array( 'step_id' => $pstd->step_id, 'previous_step_id' => $pstd->previous_step_id ) ) )
					$pstd->save();
			}
			unset( $this->mnm_records['PreviousStepsToDisplay'] );
		}
		parent::saveMNM();
	}
	
	public function deleteMNM( $relation, $id, $type = '' ) {
		if( $relation == 'PreviousStepsToDisplay' ) {
			$pstd = new PreviousStepsToDisplay( $id );
			$pstd->destroy();
		} else
			parent::deleteMNM( $relation, $id, $type );
	}
	
	public function getManyToManyRelations() {
		$relations = parent::getManyToManyRelations();
		$relations[] = 'PreviousStepsToDisplay';
		return $relations;
	}
	
	public function getPredefinedData() {
		global $booklet;

		$data = new PredefinedSteps();
		$data = $data->getPredefinedSteps();
		$type = $this->getStepType();
		$values = array();
		foreach( $data as $row ) {
			if( $row['step_type_name'] == $type->name && (empty( $row['description'] ) || $row['description'] == $booklet->booklet_code) ) {
				$values[$row['id']] = $row['Text'];
			}
		}
		return $values;
	}

	public function addPredefinedData( $Text ) {
		return $this->updatePredefinedData( NULL, $Text );
	}

	public function updatePredefinedData( $id, $Text ) {
		global $booklet;
		$pd_step = array( 'id' => $id, 'step_type_id' => $this->attributes['step_type_id'], 'Text' => $Text, 'description' => $booklet->booklet_code );
		$pds = new PredefinedSteps($pd_step);
		return $pds->save() ? $pds->getId() : FALSE;
	}

	public function deletePredefinedData( $id ) {
		$pds = new PredefinedSteps( $id );
		return $pds->destroy();
	}
	
	public function isSortable( $relation ) {
		switch( $relation ) {
			case "Fields":
      case "Choices":
      case "Questions":
        return TRUE;
			default:
				return parent::isSortable( $relation );
		}
	}

  public function isIndividual() {
    return $this->individual_rank || $this->getStepType()->getView()->class_name == 'MultipleChoiceView';
  }

	public function getFields() {
		if( empty( $this->step_fields ) ) {
			$field = new Field();
			$this->step_fields = $field->find(
				array( 'step_fields.step_id = ?', $this->getId() ),
				'step_fields.position',
				NULL,
				NULL,
				"INNER JOIN step_fields ON fields.id = step_fields.field_id" );
		}
		return $this->step_fields;
	}

  public function getQuestions() {
		if( empty( $this->questions ) ) {
			$question = new Question();
			$this->questions = $question->find(
				array( 'step_questions.step_id = ?', $this->getId() ),
				'step_questions.position',
				NULL,
				NULL,
				"INNER JOIN step_questions ON questions.id = step_questions.question_id" );
		}
		return $this->questions;
	}
	
    public function getChoices() {
        if( empty( $this->step_choices ) ) {
            $choice = new Choice();
            $this->step_choices = $choice->find(
                array( 'step_choices.step_id = ?', $this->getId() ),
                'step_choices.position',
                NULL,
                NULL,
                "INNER JOIN step_choices ON choices.id = step_choices.choice_id" );
        }
        return $this->step_choices;
    }
    
	public function getSpecificFields() {
		if( empty( $this->specific_fields ) ) {
			$this->specific_fields = array();
			foreach( $this->getFields() as $field ) {
				$this->specific_fields[] = Field::createSpecific( $field );
			}
		}
		return $this->specific_fields;
	}
	
	public function update_attributes( $attributes ) {
		$field_ids = $attributes['field_id'];
		if( !empty( $field_ids ) ) { // fields are submitted also
			$fields = array();
			foreach( $field_ids as $i => $id ) {
				$field = new Field( array(
					'id' => $id,
					'name' => $attributes['field_name'][$i],
					'type' => $attributes['field_type'][$i],
					'options' => $attributes['field_options'][$i]
				) );
				$field->save();
				$fields[] = $field->getId();
			}
			$attributes['Fields'] = $fields;
		}

        $choice_ids = $attributes['choice_id'];
        if( !empty( $choice_ids ) ) { // fields are submitted also
            $choices = array();
            foreach( $choice_ids as $i => $id ) {
                $choice = new Choice( array(
                    'id' => $id,
                    'name' => $attributes['choice_name'][$i],
                    'stage_id' => $attributes['choice_stage'][$i]
                ) );
                $choice->save();
                $choices[] = $choice->getId();
            }
            $attributes['Choices'] = $choices;
        }

		parent::update_attributes( $attributes );
	}
	
	public function showSpecificEditors( $tw = FALSE ) {
		$tw_fields = array();
		if( $tw ) {
			foreach( $tw->getCustomFields() as $field ) {
				$tw_fields[$field->field_id] = $field;
			}
		}
		
		foreach( $this->getSpecificFields() as $field ) {
			if( isset( $tw_fields[$field->getId()] ) ) {
				$tw_field = $tw_fields[$field->getId()];
			} else {
				$tw_field = new TeamWorkField( array('TeamWork_id' => $tw ? $tw->getId() : FALSE, 'value' => FALSE ) );
			}
			echo $field->renderSpecificEditor( $tw_field );
		}
	}
	
	protected function showEditor( $record) {
		global $coach;
		if( $coach )
			$objects = $this->find( array( 'coach_id = ? or public', $coach->getId() ), 'name' );
		else
			$objects = $this->find( null, 'name' );
		$evalstep = new EvaluationStep();
		if( $coach )
			$objects = array_merge( $objects, $evalstep->find( array( 'coach_id' => $coach->getId() ), 'name' ) );
		else
			$objects = array_merge( $objects, $evalstep->find( null, 'name' ) );
?>
		<select name="<?=AkInflector::pluralize( get_class( $this ) )?>[]">
<?
		foreach( $objects as $obj ) { ?>
			<option value="<?= get_class( $obj ) == 'EvaluationStep' ? 'evaluation_step' : '' ?><?= $obj->getId() ?>"><?=htmlspecialchars( $obj->name )?>:<?=$obj->getId()?></option>
<? 		} ?>
		</select>
<?
	}
    
    public function getLayoutTypes(){
        if( $this->isSortType()){
            $views = array(
                'ajax'=>'Vertical',
                'desktop'=>'Horizontal',
                'accordion'=>'Stacked'
            );
        } elseif( $this->isSelectType()) {
            $views = array(
                'blogger'=>'Blogger',
                'phpbb'=>'VPSbb',
                'easel'=>'EaselPad',
                'phpbb_comm'=>'Ideas with Comments',
                'table'=>'Table View'
            );
        } elseif( $this->isSolutionsType()){
            if( $this->isPhotoView()) {
                $views = array(
                    'old'=>'Scrolling Textbox',
                    'new'=>'Table List'
                );
            } else {
                $views = array(
                    'old'=>'Scrolling Textbox',
                    'new'=>'Table List',
                    'ajax'=>'AJAX Boxes',
                    'desktop'=>'Sticky Notes',
                    'accordion'=>'Accordion',
                    'blogger'=>'Blogger',
                    'phpbb'=>'VPSbb',
                    'phpbb_comm'=>'Ideas with Comments',
                    'easel'=>'Easel Pad',
                    'table'=>'Table View',
                    'nstable'=>'New Table View',
                    'untitled'=>'Cool blue',
                    'ipod'=>'iPod List',
                    'tiles'=>'Tiles',
                    'light' => 'Light',
                    'twitter' => 'Twitter',
                    'innovation' => 'Innovation'
                );
            }
        } else {
            $views = FALSE;
        }
        return $views;
    }
    
    public static function getDynamicSteps(){
        $step = new Step;
        $steps = array();
        foreach( $step->find( array('dynamic'=> TRUE),'name') as $step){
            $steps[$step->getId()] = $step->name;
        }
        return $steps;
    }

    public function like( $num, $booklet, $student ) {
		if( $this->use_like_feature ) {
			$tw = $booklet->getTeamWork( $this );
			$sort_order = $num;
			$students = $tw[$sort_order]->Text ? explode( ',', $tw[$sort_order]->Text ) : array();
			$students[] = $student->getId();
			sort( $students );
			if( $this->use_like_unique){
				$students = array_unique( $students );
			}
			$booklet->setTeamWork( $this, array(
				$sort_order => implode( ',', $students )
			) );
			$booklet->save();
			return sizeof( $students );
        }
        return 0;
	}
    
    # used to format a JSON response on the API
	public function to_arr() {
		$step_type = "unknown";
		switch( $this->getStepType()->getView()->class_name ) {
			case 'DevelopIdeaView':
				$step_type = 'develop';
				break;
			case 'GenerateIdeaView':
				$step_type = 'generate';
				break;
			case 'ResearchView':
				$step_type = 'research';
				break;
			case 'SelectIdeaView':
				$step_type = 'vote';
				break;
		}
		return array(
			'title' => $this->getStage()->use_idea_word ? ($this->step_title ? $this->step_title : ucwords( AkInflector::pluralize( $this->idea_word ) ) ) : "Step ".$this->getOrder(),
			'step_num' => $this->getOrder(),
			'step_type' => $step_type,
			'instructions' => $this->summary,
			'description' => $this->description,
			'needed_ideas' => $this->needed_ideas
		);
	}
    
    public function getHumanName( $booklet_id = null){
        return $this->getStage()->use_idea_word
            ? htmlspecialchars($this->step_title ? $this->step_title : ucwords( AkInflector::pluralize( $this->idea_word )))
            : "step " . $this->getOrder( $booklet_id);
    }
}
?>
