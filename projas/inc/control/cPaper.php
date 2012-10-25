<?php 

/*
 *
 *
 * @author		Lauren Bradford
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: cPaper.php,v 1.2 2006-04-23 14:57:32 dan Exp $
 *
 * @revised by:  Dan Netzer
 *
 */
 
include_once (PROJAS_OBJECT.'/oPaper.php');
include_once (PROJAS_CONTROL.'cSubmission.php');
include_once (PROJAS_CONTROL.'cPaperManager.php');

class Paper {
    
    static public function getObject($paper_id=NULL){
        global $myDB;
        
        $oPaper = new oPaper($paper_id);
        
        /*-- set paper object --*/
        if(!is_null($paper_id)){
            $sql = "SELECT p.*, pp.published_paper_id
                    FROM papers p
                    LEFT OUTER JOIN published_papers pp ON(pp.paper_id=p.paper_id)
                    WHERE p.paper_id = '$paper_id'";
                    
            $results = $myDB->getRow($sql);
            $oPaper = $myDB->assignRowValues($oPaper, $results);
            $oPaper->is_published = $results['published_paper_id'];
        }
        
        /*-- set paper collections --*/
        $oPaper = Author::collectAuthors($oPaper);
        $oPaper->media = Media::getMediaItems($oPaper->paper_id);
        $oPaper = Reviewer::collectReviewers($oPaper);

        DEBUG((array)$oPaper, 'Initialize Paper Object:', true, SQL);    
        return $oPaper;
    }
    
    static public function getFormObject($paper_id=NULL){
        $oPaper = self::getObject($paper_id);
		$oPaper = DB::assignFormValues($oPaper);
		return $oPaper;
    }		
    
    static public function publishActive($oPaper){
        if($oPaper->decision == '1'){
            return true;
        } else {
            return false;
        }
    }
    
   /*
    *  @notes: used by cPublish, to load oPubPaper object
    */
	static protected function loadChild($object){
	    global $myDB;
	    
        $sql = "SELECT * FROM papers WHERE paper_id = '$object->paper_id'";
        $results = $myDB->getRow($sql);
        $object = $myDB->assignRowValues($object, $results, 'oPaper');
        $object = Author::collectAuthors($object,true);
        $object->media = Media::getMediaItems($object->paper_id,NULL,NULL,true);
        return $object;
    }
    
    static public function saveObject($oPaper){
        global $myDB;
        
        $oPaper->update_date = 'NOW()';
        
            if($oPaper->paper_id){         
                $sql = $myDB->generateUpdateSQL($oPaper, 'oPaper');
                $myDB->runQueryUpdate($sql);
            } else {
                $sql = $myDB->generateInsertSQL($oPaper, 'oPaper');
                $oPaper->paper_id = $myDB->runQueryInsert($sql);
            }
            
        return $oPaper;
    }
    
    static public function getPaperCount(){
        global $myDB;
        
        $sql = "SELECT COUNT(*) AS count
                FROM papers";
        $result = $myDB->getRow($sql);
        return $result['count'];
    }
    
    static public function getUnderReviewCount(){
        global $myDB;
        
        $sql = "SELECT COUNT(*) as count
                FROM papers 
                WHERE decision IS NULL";
        $result = $myDB->getRow($sql);
        return $result['count'];
    }
    
    static public function getOverDueCount(){
        global $myDB;
        
       $sql = "SELECT COUNT(DISTINCT P.paper_id) AS count 
               FROM papers P 
               LEFT OUTER JOIN users U ON P.editor_user_id= U.user_id 
               LEFT OUTER JOIN people PE ON PE.people_id = U.people_id 
               WHERE P.decision IS NULL AND 
               EXISTS(SELECT PR.reviewer_id 
                      FROM paper_reviewers PR 
                      WHERE PR.date_received IS NULL AND 
                      PR.date_due <= CURDATE() AND 
                      PR.paper_id = P.paper_id)";
        $result = $myDB->getRow($sql);
        return $result['count'];
    }
    
    static public function getPublishedCount(){
                global $myDB;
        
        $sql = "SELECT COUNT(*) AS count
                FROM papers p
                INNER JOIN published_papers pp ON(p.paper_id=pp.paper_id)
                WHERE pp.is_active = '1'";
        
        $result = $myDB->getRow($sql);
        return $result['count'];
    }
    
    static public function getMyPapersCount(){
                global $myDB;
        
        $sql = "SELECT COUNT(*) AS count
                FROM papers
                WHERE editor_user_id = '$_SESSION[user_id]'";
        
        $result = $myDB->getRow($sql);
        return $result['count'];
    }
    
    static public function deletePaper($paper_id){
        global $myDB;

        $sql = "SELECT p.paper_id
                FROM papers p
                LEFT OUTER JOIN media_items m ON(p.paper_id=m.paper_id)
                LEFT OUTER JOIN paper_authors a ON(p.paper_id=a.paper_id)
                LEFT OUTER JOIN paper_reviewers r ON(p.paper_id=r.paper_id)
                WHERE p.paper_id = '$paper_id' AND
                (
                    m.paper_id IS NOT NULL OR
                    a.paper_id IS NOT NULL OR
                    r.paper_id IS NOT NULL
                )";
                
        $result = $myDB->getRow($sql);
        
        if($result){
            return false;
        } else {
            $sql = "DELETE from papers WHERE paper_id = '$paper_id'";
            $myDB->runQueryUpdate($sql);
            $sql = "DELETE from published_papers WHERE paper_id = '$paper_id'";
            $myDB->runQueryUpdate($sql);
            return true;
        }
    }
}
?>