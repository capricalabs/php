<?php
/*
 *
 *
 *
 * @author		LB, DWN
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: cReviewer.php,v 1.1.1.1 2006-02-08 02:24:33 dan Exp $
 *
 *
 */

include_once (PROJAS_OBJECT.'/oReviewer.php');
include_once (PROJAS_CONTROL.'/cPerson.php');

class Reviewer extends Person {
	
	static public function deleteReviewer($reviewer_id){
        global $myDB;
        $sql = "DELETE FROM paper_reviewers
                WHERE reviewer_id = '$reviewer_id'";
        $myDB->runQueryUpdate($sql);
    }

    static public function getObject($reviewer_id=NULL,$people_id=NULL){
        global $myDB;
        
        $oReviewer = new oReviewer($reviewer_id);
        $oReviewer->people_id = $people_id;
        
        if(!is_null($reviewer_id)){
            $sql = "SELECT * FROM paper_reviewers WHERE reviewer_id = '$reviewer_id'";
            $results = $myDB->getRow($sql);
            $oReviewer = $myDB->assignRowValues($oReviewer, $results);
        }
        
        if($oReviewer->people_id){
            /*-- load parent person properties --*/
            $oReviewer = Person::loadChild($oReviewer);
        }

        DEBUG((array)$oReviewer, 'Initialize Reviewer Object:', true, SQL);
        return $oReviewer;
    }
    
    static public function collectReviewers($oPaper){
        global $myDB;
        $sql = "SELECT reviewer_id FROM paper_reviewers WHERE paper_id = '$oPaper->paper_id'";
        $results = $myDB->select($sql);
            if(count($results) && is_array($results)){
                foreach($results AS $reviewer){
                    $oReviewer = self::getObject($reviewer[reviewer_id]);
                    array_push($oPaper->reviewers,$oReviewer);
                }
            }
            
        return $oPaper;
    }
    
    static public function getFormObject($reviewer_id=NULL ,$people_id=NULL){
        $oReviewer = self::getObject($reviewer_id, $people_id);
		$oReviewer = DB::assignFormValues($oReviewer);
		/*--assign parent properties--*/
		$oReviewer = DB::assignFormValues($oReviewer, 'oPerson');
		return $oReviewer;
    }	
    
    static public function saveObject($oReviewer){
        global $myDB;
        
        /*-- save parent properties in db --*/
        $oReviewer = Person::saveObject($oReviewer);
        
            if($oReviewer->reviewer_id){         
                $sql = $myDB->generateUpdateSQL($oReviewer);
                $myDB->runQueryUpdate($sql);
            } else {
                $sql = $myDB->generateInsertSQL($oReviewer);
                $oReviewer->reviewer_id = $myDB->runQueryInsert($sql);
            }
            
        return $oReviewer;
    }
}		
?>