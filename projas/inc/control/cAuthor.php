<?php
/*
 *
 *
 *
 * @author		DWN
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: cAuthor.php,v 1.5 2006-03-27 22:22:39 dan Exp $
 *
 *
 */
 
include_once (PROJAS_OBJECT.'/oAuthor.php');
include_once (PROJAS_CONTROL.'/cPerson.php');

class Author extends Person {
    
    static function getObject($author_id=NULL,$people_id=NULL){
        global $myDB;
        
        $oAuthor = new oAuthor($author_id);
        $oAuthor->people_id = $people_id;
        
            if(!is_null($author_id)){
                $sql = "SELECT * FROM paper_authors WHERE author_id = '$author_id'";
                $results = $myDB->getRow($sql);
                $oAuthor = $myDB->assignRowValues($oAuthor, $results);
            }

            if($oAuthor->people_id){
                /*-- load parent person properties --*/
                $oAuthor = Person::loadChild($oAuthor);
            }
            
        DEBUG((array)$oAuthor, 'Initialize Author Object:', true, SQL);
        return $oAuthor;
    }
    
    static public function collectAuthors($oPaper,$is_publish=NULL){
        global $myDB;
        $sql = "SELECT author_id FROM paper_authors WHERE paper_id = '$oPaper->paper_id'";
            if(!is_null($is_publish)){
                $sql .= " AND is_published = '1'";
            } else {
                $sql .= " AND (is_published != '1' OR is_published IS NULL)";
            }
            
        $sql .= " ORDER BY author_id ASC";
        
        $results = $myDB->select($sql);
            if(count($results) && is_array($results)){
                foreach($results AS $author){
                    $oAuthor = self::getObject($author[author_id]);
                    array_push($oPaper->authors,$oAuthor);
                }
            }
            
        return $oPaper;
    }
    
    static public function getContact($oAuthors){
        if(is_array($oAuthors) && count($oAuthors)){
                foreach($oAuthors AS $oAuthor){
                    if($oAuthor->is_contact){
                        return $oAuthor;
                    }
                }
            return false;
        }
    }
    
    static public function deleteAuthor($author_id){
        global $myDB;
        $sql = "DELETE FROM paper_authors
                WHERE author_id = '$author_id'";
        $myDB->runQueryUpdate($sql);
    }

    static public function getFormObject($author_id=NULL,$people_id=NULL){
        $oAuthor = self::getObject($author_id, $people_id);
        /*-- set posted value for checkbox, since automater will ignore null --*/
        $oAuthor->is_contact = $_POST[is_contact];
		$oAuthor = DB::assignFormValues($oAuthor);
		/*--assign parent properties--*/
		$oAuthor = DB::assignFormValues($oAuthor, 'oPerson');
		return $oAuthor;
    }

    static public function saveObject($oAuthor){
        global $myDB;
        
        /*-- save parent properties in db --*/
        $oAuthor = Person::saveObject($oAuthor);

            if($oAuthor->author_id){         
                $sql = $myDB->generateUpdateSQL($oAuthor);
                $myDB->runQueryUpdate($sql);
            } else {
                $sql = $myDB->generateInsertSQL($oAuthor);
                $oAuthor->author_id = $myDB->runQueryInsert($sql);
                
                if ($_POST[submode] == "process_author") {
                    Submission::processAuthor($oAuthor->submission_author_id);
				}
            }
            
        return $oAuthor;
    }
}
?>