<?php
/*
 *
 *
 *
 * @author		DWN<dogtowngeek@gmail.com>
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: cPublish.php,v 1.2 2006-03-30 21:44:22 dan Exp $
 *
 *
 */
 
include_once (PROJAS_OBJECT.'/oPubPaper.php');
include_once (PROJAS_CONTROL.'cPaper.php');
 
class cPublish extends Paper {
    
    static function getObject($paper_id=NULL){
        global $myDB;
        
        $oPubPaper = new oPubPaper($paper_id);
        
            if(!is_null($paper_id)){
                $sql = "SELECT * FROM published_papers WHERE paper_id = '$paper_id'";
                $results = $myDB->getRow($sql);
                $oPubPaper = $myDB->assignRowValues($oPubPaper, $results);
            }
            
            if($oPubPaper->paper_id){
                /*-- load parent person properties --*/
                $oPubPaper = Paper::loadChild($oPubPaper,true);
            } else {
                return false;
            }
            
        DEBUG((array)$oPubPaper, 'Initialize PubPaper Object:', true, SQL);
        return $oPubPaper;
    }
    
    static public function getFormObject($paper_id=NULL){
        $oPubPaper = self::getObject($paper_id);
		$oPubPaper = DB::assignFormValues($oPubPaper);
		/*--assign parent properties--*/
		$oPubPaper = DB::assignFormValues($oPubPaper, 'oPaper');
		return $oPubPaper;
    }	
    
    static public function saveObject($oPubPaper,$only_pub=false){
        global $myDB;
        
            /*-- save parent properties in db --*/
            if($only_pub === false){
                $oPubPaper = Paper::saveObject($oPubPaper);
            }
            if($oPubPaper->published_paper_id){         
                $sql = $myDB->generateUpdateSQL($oPubPaper,'oPubPaper');
                $myDB->runQueryUpdate($sql);
            } else {
                $sql = $myDB->generateInsertSQL($oPubPaper,'oPubPaper');
                $oPubPaper->published_paper_id = $myDB->runQueryInsert($sql);
            }
            
        return $oPubPaper;
    }
    
    static function getLastPublish(){
        global $myDB;
        
        $sql = "SELECT MAX(regenerated_date) as date
                FROM published_log";
              
        $result = $myDB->getRow($sql);
        
        return $result['date'];
    }
}
?>