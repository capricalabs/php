<?php

class DevelopIdeaView implements ViewInterface {
	public function render() {
		global $template, $booklet, $student, $LANG_ERROR;
		$step = func_get_arg( 0 );
		$steps = $template->getSteps();
		
		# check step status
		if( !$step->isActiveStep( $booklet->scenario_id ) ) {
			list( $start_time, $end_time ) = $step->scenarioStartEndTimes( $booklet->scenario_id );
			echo "<h2 class=\"subtitle\">Step ".$step->getOrder()." :: ".$step->name."</h2><div class='container'>This step is currently not active. Time restrictions applied from ".date( 'm/d/Y g:iA', strtotime( $start_time ) )." to ".date( 'm/d/Y g:iA', strtotime( $end_time ) ).".</div>";
			return;
		}
		
		if( isset( $_REQUEST[SYS_FORM_ACT_CMD] ) && $_REQUEST[SYS_FORM_ACT_CMD] == "update" ) {
			$SYS_ERROR_CODE = $this->doSave( $step, $student, $booklet, $_REQUEST['formIdea'], $_REQUEST['formOriginalIdea'] );
		}
		
		if($_REQUEST['param'] == 'ipad')
			die($SYS_ERROR_CODE);
		
		if( $_REQUEST['lock_page'] ) {
			// lock the current page for this student
			$booklet->LockPage( $student, $step->getId() );
		}
		// save page information for future use
		$ARR_IDEAS_INFO = $booklet->getTeamWork( $step );
		if( !$ARR_IDEAS_INFO[0] ) {
			# we need this idea id for collab feature
			$booklet->SetMainIdeas( array( 0 => ' ' ), array( 0 => '' ), $step );
			$booklet->save();
			$ARR_IDEAS_INFO = $booklet->getTeamWork( $step );
		}
		$lock = $booklet->RetrieveLocks( $step->getId() );

		$arrSolutionsByCriteriaRank = array();
		if( $step->getOrder() > 4 ) {
			$solutions_step = $steps[$step->getOrder() - 1 + $step->solutions_offset];
			$criteria_step = $steps[$step->getOrder() - 1 + $step->criteria_offset];
			$ranking_step = $steps[$step->getOrder() - 1 + $step->rank_offset];
			$max_rankings = $ranking_step->needed_ideas;

            $arrRankingInfo  = $booklet->getTeamWork( $ranking_step );
			$ARR_CRITERIA_INFO = $booklet->getTeamWork( $criteria_step );
			$arrSolutionsInfo  = $booklet->getTeamWork( $solutions_step );

			$space = "";
			foreach( $arrRankingInfo as $solutionIndex => $rankInfo ) { 
				$avg = 0;
				foreach( array_keys( $ARR_CRITERIA_INFO ) as $cn => $c ) { if(is_object($rankInfo)) {
					$r = $rankInfo->rating( $cn );
					$avg += $r;
					if( !isset( $arrSolutionsByCriteriaRank[$c] ) ) {
						$arrSolutionsByCriteriaRank[$c] = array();
					}
					if( !isset( $arrSolutionsByCriteriaRank[$c][$r] ) ) {
						$arrSolutionsByCriteriaRank[$c][$r] = array();
					}
					$arrSolutionsByCriteriaRank[$c][$r][] = $solutionIndex;
				} }
				if( $avg ) {
					// This is a lousy solution to distinguish between ideas with the same average
					$arrSolutionsByCriteriaRank["AVG"][$avg . $space] = $solutionIndex;
					// It relies on the HTML and JavaScript parseInt to ignore the extra spaces
					$space .= " ";
				}
			}
			if( $arrSolutionsByCriteriaRank["AVG"] ) {
				krsort( $arrSolutionsByCriteriaRank["AVG"] );
			}
			
			$top = array();
			foreach($arrRankingInfo as $k => $st)
			{
				if($st->CriteriaRanks){
					$top[array_sum(explode(',',$st->CriteriaRanks))] = $k;
                }
			}
			krsort($top);
		}
		
		//dump($top);
		//dump($topinfo,1);
        
        if( $step->getOrder() > 1 ) {
            $prevStep = $steps[$step->getOrder() - 2];
            $prevStepIdeas = $booklet->getTeamWork( $prevStep );
            if( count( $prevStepIdeas ) == 1 ) {
                $prevStepData = $prevStepIdeas[0]->Text;
            }
        }

		require( Theme::getViewPath( $this->getViewFile() ) );
	}
	
	public function doSave( $step, $student, $booklet, $idea, $original_idea ) {
		global $LANG_ERROR;
		# Check the page lock status
		$lock = $booklet->RetrieveLocks( $step->getId() );
		$arrConflictIdeas = array( );
		if( !$lock || $lock->student_id == $student->getId() ) {
			if( is_uploaded_file( $_FILES["formIdea_file"]['tmp_name'] ) ) {
				$arr = array( 'idea' => $idea, 'file' => file_get_contents( $_FILES["formIdea_file"]['tmp_name'] ), 'name' => $_FILES["formIdea_file"]['name'], 'mime' => $_FILES["formIdea_file"]['type'] );
				$idea = $arr;
			}
			$arrConflictIdeas = $booklet->SetMainIdeas( array( 0 => $idea ), array( 0 => $original_idea ), $step );
			if( count( $arrConflictIdeas ) == 0 ) {
				$SYS_ERROR_CODE = ( $booklet->save( ) )? $LANG_ERROR[ ERRCODE_IDEAS_OK ]: $LANG_ERROR[ ERRCODE_IDEAS_FAILED ];
				$SYS_ERROR_CODE = sprintf( $SYS_ERROR_CODE, $step->idea_word );
				$Saved = true;
			} else {
				$SYS_ERROR_CODE = "CONFLICT";
				$Saved = false;
			}
			$booklet->AddTimeMachineEntry( $student, $step, 1, $idea, $Saved );
		}
		return $SYS_ERROR_CODE;
	}
	
    protected function getViewFile(){
        return 'develop_idea_view.php';
    }
    
    public function isDevelopType(){
        return TRUE;
    }
    
	public function handle() {
	}
	
	public function statistics( $step, $booklet ) {
		$tw = $booklet->getTeamWork( $step );
		if( !empty( $tw ) && ($tw[0]->Type() != 'Idea' || $tw[0]->Text) )
			return "$step->idea_word Developed";
		else
			return "$step->idea_word Not Developed";
	}
	
	public function virtualbooklet( $step, $booklet, $template = DEFAULT_THEME ) {
		$tw = $booklet->getTeamWork( $step );
        $view = str_replace('_view.','_virtualbooklet.', $this->getViewFile());
        if( strstr( $template, 'table' ) ){
            $template = "table";
        }
        require( Theme::getViewPath( $view, $template));
	}
	
	public function mastersheet( $step, $team_work ) {
        $view = str_replace('_view.','_mastersheet.', $this->getViewFile());
		require( SYS_CLASSES_DIR."views/table/$view" );
	}

	public function api_render( $step, $booklet, $student ) {
		$ideas = array();
		$tws = $booklet->getTeamWork( $step );
		foreach( $tws as $tw ) {
			$ideas[] = $tw->to_arr();
		}
		return $ideas;
	}
}

?>
