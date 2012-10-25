<?php
/*
 *
 *
 *
 * @author		DWN<dogtowngeek@gmail.com>
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: cSubmission.php,v 1.7 2006-03-19 21:19:39 dan Exp $
 *
 *
 */

include_once (PROJAS_OBJECT.'/oSubmission.php');
include_once (PEAR_LIBRARY.'Wddx.php');
include_once (PROJAS_CONTROL.'/cMedia.php');

class Submission extends cMasterController{

    /*-- public --*/
    public $oSubmission;	
    public $oMedia;
    
    function __construct(){
        $this->template = 'submit.tmpl';
    }
    
    function renderPage() {
        switch($this->action) {
            
            case "publish":
                
                session_unset(); 
                $_SESSION[submission_mode] = "publish";
                $this->formInclude='submit1-b';
                break;
                
            case "delete_media":
                
                $oMedia = Media::getMediaItem($_POST[media_item_id]);
                Media::deleteMediaItem($oMedia);
                $this->success_message = "Media Item has been deleted.";
                $this->action = 'upload_media';
                $this->renderPage();
                break;
              
            case "save_media":
                
                $oMedia = Media::getFormMediaObject($_POST[media_item_id]);
                $oMedia = Media::saveMedia($oMedia);

                    if(!$oMedia->media_item_id){
                        $this->error_message = 'No file or url were submitted.';
                        $this->oTemp = $oMedia;
                    } else {
                        $this->success_message = "We have saved the information you have entered. <br>You can now upload additional files.";
                    }
                
                $this->action = 'upload_media';
                $this->renderPage();
                break;
            
            case "commence":
                
                $this->storeData();
                
                    if(is_numeric($this->oSubmission->author_count)){
                        $this->formInclude='submit2';
			            $this->pgtitle = 'Input Paper Data';
                        break;
                        
                    } else {
                        $this->error_message="Please enter a valid number of authors.";
                    }
            
            case "prelim":

                if($_POST[submode] == "edit"){
                    $this->restoreData();
                }
                
                $this->formInclude='submit1-b';
                break;
                
            case "step1":
                
                session_unset(); 
                $this->formInclude='submit1';
                break;
            
            case "set_paper_info":
                
                $this->storeData();

                    if($this->validate()){
                        $this->action = 'step3';
                        $this->renderPage();
                        break;
                    }
                
            case "step2":
                
                if($_POST[submode] == "edit"){
                    $this->restoreData();
                } else {
                    $this->storeData();
                }
                
                $this->formInclude='submit2';
			    $this->pgtitle = 'Input Paper Data';
                break;
                
            case "step3":
                
                $this->formInclude='submit3';
			    $this->pgtitle = 'Confirm Paper Data';
                break;
            
            case "media_complete":
            
                $this->formInclude='submit5';
			    $this->success_message="You have completed the Submission process.";
                break;
            
            case "edit_media":
            
                $this->oSubmission = $this->loadObject($_POST[submission_id]);
                $oMedia = Media::getMediaItem($_POST[media_item_id]);
                $this->oMedia = $oMedia;
                $this->formInclude='submit4-b';
                break;
                
            case "upload_media":
            
                    if(!is_object($this->oSubmission)){
                        $this->oSubmission = $this->loadObject($_POST[submission_id]);
                    }
                    
                $this->formInclude='submit4';
                break;
                
            case "paper_confirmed":

                $this->restoreData();
                    if(is_object($this->oSubmission) && is_null($_SESSION['saved'])){
                        $this->saveObject($this->oSubmission);
                        $this->success_message="Paper saved, now upload all files.";
                        $_SESSION['oSubmission'] = serialize($this->oSubmission);
                        $_SESSION['saved'] = true;
                    } else {
                        $this->error_message="Paper has already been saved, now upload media.";
                    }
                
			    $this->action = "upload_media";
			    $this->renderPage();
                break;

            default:
                exit("No mode is set");
        }
    }  
   
    private function storeData(){
        $this->setFormObject($_POST[submission_id]);
            if($_POST['author_count']){
                $this->oSubmission->author_count = $_POST['author_count'];
            }
        $_SESSION[oSubmission] = serialize($this->oSubmission);
    }
    
    private function restoreData(){
        $this->oSubmission = unserialize($_SESSION[oSubmission]);
    }
    
    static function saveObject(&$oSubmission) {
        $remoteDB = new DB(true);

            if($oSubmission->submission_id){    
                self::deleteSubAuthors($oSubmission->submission_id, $remoteDB);
                $sql = $remoteDB->generateUpdateSQL($oSubmission);
                $remoteDB->runQueryUpdate($sql);
            } else {
                $oSubmission->date_received = 'NOW()';
                $sql = $remoteDB->generateInsertSQL($oSubmission);
                $oSubmission->submission_id = $remoteDB->runQueryInsert($sql);
            }
    
            if(count($oSubmission->authors)){
                foreach ($oSubmission->authors AS $oAuthor){
                    $oAuthor->submission_id = $oSubmission->submission_id;        
                    $sql = $remoteDB->generateInsertSQL($oAuthor);
                    $remoteDB->runQueryInsert($sql);
                }
            } 
        
        $remoteDB->closeConnection();
    }
 
    static function loadObject($submission_id=NULL) {
        $remoteDB = new DB(true);
        
        $oSubmission = new oSubmission($submission_id);
        
            if($oSubmission->submission_id){
            
                /*-- load submission paper data --*/
                
                $sql = "SELECT * 
                        FROM submissions 
                        WHERE submission_id = '$oSubmission->submission_id'";
                       
                $results = $remoteDB->getRow($sql);

                    if($results){
                        $oSubmission = $remoteDB->assignRowValues($oSubmission, $results);
                    }   
                    
                /*-- load associated authors --*/
        
                $sql = "SELECT submission_author_id
                        FROM submission_authors 
                        WHERE submission_id = '$oSubmission->submission_id'";        
                   
                $results = $remoteDB->select($sql);
            
                    if($results){
                        foreach($results AS $author){
                            $oSubmission->authors[] = self::getSubAuthor($author['submission_author_id']);
                        }
                    }
                    
                /*-- load associated submission media --*/
                $oSubmission->media = Media::getMediaItems(NULL, $oSubmission->submission_id,true);
            }
            
        $remoteDB->closeConnection();
        DEBUG((array)$oSubmission, 'Submission Object:', true, SQL);
        return $oSubmission;
    }
    
    static public function getSubAuthor($submission_author_id=NULL){
        $remoteDB = new DB(true);
        
        $oSubAuthor = new oSubAuthor($submission_author_id);
        
        if(!is_null($submission_author_id)){
            $sql = "SELECT * 
                    FROM submission_authors 
                    WHERE submission_author_id = '$submission_author_id'";
                    
            $results = $remoteDB->getRow($sql);
            $oSubAuthor = $remoteDB->assignRowValues($oSubAuthor, $results);
        }

        DEBUG((array)$oSubAuthor, 'Submission Author Object:', true, SQL);
        return $oSubAuthor;
    }
    
    function setFormObject($submission_id=NULL){
        $remoteDB = new DB(true);
        /*-- load submission object from session, if any --*/
        $this->restoreData();
        /*-- otherwise, create it --*/
            if(!$this->oSubmission){
                $oSubmission = $this->loadObject($submission_id);
                $this->oSubmission = $oSubmission;
            }
		$this->oSubmission = $remoteDB->assignFormValues($this->oSubmission);
		
		/*-- set these properties only if we're coming from sub page --*/
		if(count($_POST[last_name])){
		    $this->oSubmission->author_contact = $_POST[author_contact];
		    $this->oSubmission->author_address = $_POST[author_address];
		    $this->oSubmission->authors = array();
            $this->setAuthors();
        }
    }

    /*-- build submission author collection --*/
    private function setAuthors(){
        
        for($i=0;$i<count($_POST[last_name]);$i++){
            $oAuthor = new oSubAuthor();
            
                if($i==$_POST[author_contact] && !$_POST['is_publication']){
                    $oAuthor->is_contact = true;
                    $oAuthor->address = $_POST[author_address];
                }

            $oAuthor->first_name = $_POST[first_name][$i];
            $oAuthor->last_name = $_POST[last_name][$i];
            $oAuthor->middle_name = $_POST[middle_name][$i];
            $oAuthor->email = $_POST[email][$i];
            
            $this->oSubmission->authors[] = $oAuthor;
        }
    }

    public function deleteSubAuthors($submission_id, $remoteDB){
        
        $sql = "DELETE FROM submission_authors
                WHERE submission_id = '$submission_id'";
        $remoteDB->runQueryUpdate($sql);
    }
	     
    function validate() {
        $is_valid = true;

        foreach($this->oSubmission->authors AS $oAuthor){
            /*-- eat up whitespace --*/
            $oAuthor->email = trim($oAuthor->email);
                if(strlen($oAuthor->email) && !eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $oAuthor->email)){
                    $is_valid = false;
                    $this->error_message = "Each e-mail address must be a valid format."; 
                }
        }
        
        if(strlen($this->oSubmission->resub_paper_id) && !is_numeric($this->oSubmission->resub_paper_id)){
            $is_valid = false;
            $this->error_message = "Re-submission ID must be numeric."; 
        }
        
        if(strlen($this->oSubmission->paper_id) && !is_numeric($this->oSubmission->paper_id)){
            $is_valid = false;
            $this->error_message = "Paper ID must be numeric."; 
        }
        
        return ($is_valid) ? true : false ;
    }	
    
    static public function getUA_Authors($oPaper) {
        $remoteDB = new DB(true);
        
        $sql = "SELECT sa.submission_author_id, sa.first_name, sa.last_name, sa.middle_name, sa.is_contact, sa.email
                FROM submission_authors sa
                INNER JOIN submissions s ON sa.submission_id = s.submission_id 
                WHERE s.submission_id = '$oPaper->submission_id' AND 
                sa.acknowledged != '1'
                ORDER BY sa.submission_author_id ASC";

        $results = $remoteDB->getRow($sql);
        $remoteDB->closeConnection();
        
        if ($results) {
            /*-- setting here for the choose person form --*/
            $_POST[person_first_name] = $results[first_name];
            $_POST[person_middle_name] = $results[middle_name];
            $_POST[person_last_name] = $results[last_name];
            $_POST[is_contact] = $results[is_contact];
            $_POST[person_email] = $results[email];
            $_POST[submission_author_id] = $results[submission_author_id];
            $_POST[paper_id] = $oPaper->paper_id;
            return true;
        } else {
            return false;
        }
    }
    
    static function unprocessedExist($is_published=NULL){
        $remoteDB = new DB(true);
        
        $pub_sql = (!is_null($is_published)) ? "s.is_publication = '1'" : "(s.is_publication IS NULL OR s.is_publication != '1')";
        
        $results = $remoteDB->select("SELECT DISTINCT s.submission_id
                                  FROM submissions s
                                  INNER JOIN submission_authors sa ON(sa.submission_id=s.submission_id)
                                  WHERE (s.acknowledged != '1' OR
                                  sa.acknowledged != '1') AND
                                  $pub_sql AND
                                  DATE_ADD(s.date_received, INTERVAL 10 MINUTE) <= NOW()");
        
        $remoteDB->closeConnection();
        
        return count($results);
    }

    static public function getSubmissions($is_published=NULL) {
        $remoteDB = new DB(true);
        
        $pub_sql = (!is_null($is_published)) ? "s.is_publication = '1'" : "(s.is_publication IS NULL OR s.is_publication != '1')";
        
        $results = $remoteDB->select("SELECT DISTINCT s.submission_id 
                                  FROM submissions s
                                  INNER JOIN submission_authors sa ON(sa.submission_id=s.submission_id)
                                  WHERE (s.acknowledged != '1' OR
                                  sa.acknowledged != '1') AND
                                  $pub_sql AND
                                  DATE_ADD(s.date_received, INTERVAL 10 MINUTE) <= NOW()
                                  ORDER BY submission_id DESC");
                                  
        $remoteDB->closeConnection();
        
        if(count($results) && is_array($results)){
           $oSubmissions = array();
             foreach($results AS $result) {
                $oSubmissions[] = self::loadObject($result["submission_id"]);
             }
            return $oSubmissions;
        } else {
            return false;
        }
    }
    
    static public function getSubmissionCount(){
        $remoteDB = new DB(true);

        $result = $remoteDB->getRow("SELECT COUNT(*) as count
                                     FROM submissions");
        
        $remoteDB->closeConnection();
        
        return $result['count'];
    }
    
        /*-- move submission author to paper author object --*/
    static public function subToAuthor(){
        $remoteDB = new DB(true);
        
            $sql = "SELECT *
                    FROM submission_authors 
                    WHERE submission_author_id = '$_POST[submission_author_id]'";        
               
            $author = $remoteDB->getRow($sql);
        
                if($author){
                    $oAuthor = new oAuthor();
                    $oAuthor->submission_author_id = $author[submission_author_id];
                    $oAuthor->email1 = $author[email];
                    $oAuthor->first_name = $author[first_name];
                    $oAuthor->middle_name = $author[middle_name];
                    $oAuthor->last_name = $author[last_name];
                    $oAuthor->published_first_name = $author[first_name];
                    $oAuthor->published_middle_name = $author[middle_name];
                    $oAuthor->published_last_name = $author[last_name];
                    $oAuthor->address = $author[address];
                    $oAuthor->is_contact = $author['is_contact'];
                    $oAuthor->paper_id = $_POST['paper_id'];
                    $oAuthor->is_published = $_POST['is_published'];
                }
                            
                if($_POST[person_last_name]){
                    $oAuthor->first_name = $_POST[person_first_name];
                    $oAuthor->middle_name = $_POST[person_middle_name];
                    $oAuthor->last_name = $_POST[person_last_name];
                }
                
        $remoteDB->closeConnection();                    
        return $oAuthor;
    }

    static public function processAuthor($submission_author_id){
        $remoteDB = new DB(true);
            $sql = "UPDATE submission_authors 
                    SET acknowledged = '1' 
                    WHERE submission_author_id = '$submission_author_id'";
               
        $remoteDB->runQueryUpdate($sql);
        $remoteDB->closeConnection();
    }
}
?>