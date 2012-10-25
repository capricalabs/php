<?php
/*
 *
 *
 *
 * @author		DWN<dogtowngeek@gmail.com>
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: cEmail.php,v 1.1.1.1 2006-02-08 02:24:33 dan Exp $
 *
 *
 */

include_once (PROJAS_OBJECT.'/oEmail.php');

class cEmail {
	
    static public function getObject($ref=NULL,$email_id=NULL){
        global $myDB;
        
        $oEmail = new oEmail();

        $sql = "SELECT * 
                FROM emails 
                WHERE ";
        $sql .= (!is_null($ref)) ? "ref = '$ref'" : "email_id = '$email_id'";
        $result = $myDB->getRow($sql);

        $oEmail = $myDB->assignRowValues($oEmail, $result);

        DEBUG((array)$oEmail, 'Initialize Email Object:', true, SQL);
        return $oEmail;
    }
    
    static public function replaceTokens($oEmail,$tokens){
    
        	if (!is_null($tokens)) {
				list($oEmail->body) = str_replace(
					array_keys($tokens),
					array_values($tokens),
					array($oEmail->body)); 
					
			    list($oEmail->subject) = str_replace(
					array_keys($tokens),
					array_values($tokens),
					array($oEmail->subject)); 
			}
			
        return $oEmail;
    }
    
    static public function getObjects(){
        global $myDB;
        
        $sql = "SELECT * FROM emails";
        $results = $myDB->select($sql);
        $arEmails = array();
            if(count($results) && is_array($results)){
                foreach($results AS $email){
                    $arEmails[] = self::getObject($email['ref']);
                }
            }
        return $arEmails;
    }

	static public function getFormObject($email_id=NULL){
	    global $myDB;
	    
	    $oEmail = self::getObject(NULL,$email_id);
		$oEmail = $myDB->assignFormValues($oEmail);
		return $oEmail;
	}
	
    static public function saveObject($oEmail){
        global $myDB;
        
        if($oEmail->email_id){
            $sql = $myDB->generateUpdateSQL($oEmail);
            $myDB->runQueryInsert($sql);
        } else {
            $sql = $myDB->generateInsertSQL($oEmail);
            $oEmail->email_id = $myDB->runQueryInsert($sql);
        }
        return $oEmail;
    }
}		
?>