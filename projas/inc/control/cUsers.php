<?php
/*
 *
 *
 *
 * @author		DWN<dogtowngeek@gmail.com>, LB
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: cUsers.php,v 1.3 2006-02-13 21:04:11 dan Exp $
 *
 *
 */

include_once (PROJAS_OBJECT.'/oUser.php');
include_once (PROJAS_CONTROL.'/cPerson.php');

class User extends Person {
    
    public function renderPage(){
        
        switch($this->action){
            
            case "add_user":
                $this->message = 'Please enter the user\'s name';
                $this->formInclude='add_user';
                break;
            
            case "edit_user":

                $this->formInclude='edit_user';
                $oUser = self::getObject($_POST[user_id], $_POST[people_id]);
                
                    if(!$oUser->people_id){
                        $oUser->first_name = $_POST[person_first_name];
                        $oUser->middle_name = $_POST[person_middle_name];
                        $oUser->last_name = $_POST[person_last_name];
                    }
                    
                $this->oTemp = $oUser;
                break;
                
            case "update_user":
                
                $oUser = $this->getFormObject($_POST[user_id], $_POST[people_id]);
                    if(!$oUser->user_id){
                        if($this->validate($oUser) === false ){
                            $this->action='edit_user';
                            $this->renderPage();
                            break;
                        }
                    }
                    
                $this->success_message = 'User has been updated.';    
                $this->saveObject($oUser);
                $this->action = 'list';
                $this->renderPage();
                break;
                
            case "choose_person":
            
                $this->message = "Please associate existing person, or create new";
                $this->form_action='/users.php';
                $this->formInclude='choose_person';
                break;
                
            case "person_chosen":
            
                $this->message = 'Edit User Info';
                $this->action = 'edit_user';
                $this->renderPage();
                break;
                
            case "edit_password":
                $this->message = 'Update user with desired password.'; 
                $oUser = self::getObject($_POST[user_id]);
                $this->oTemp = $oUser;
                $this->formInclude='edit_user_password';
                break;
                
            case "update_password":
                $this->updatePassword($_POST[user_id]);
                break;
                
            case "delete":
                $this->success_message = 'User has been deleted from system.';
                self::deleteUser($_POST[user_id]);
                
            case "list":
            default:
                $this->pgtitle = 'All Users';
                $this->formInclude='user_list';
                $this->content = self::getObjects();
        }
    }
    
    private function validate($oUser){
        if($oUser->password == trim($_POST[password_confirm])){
            return true;
        } else {
            $this->error_message = 'Passwords don\'t match, please try again.';
            return false;
        }
    }
    
    static public function getObject($user_id=NULL,$people_id=NULL){
        global $myDB;
        
        $oUser = new oUser($user_id);
        $oUser->people_id = $people_id;

            if(!is_null($user_id) && strlen(trim($user_id))){
                $sql = "SELECT u.*,ut.user_type
                        FROM users u
                        LEFT OUTER JOIN user_types ut ON (u.user_type_id=ut.user_type_id)
                        WHERE user_id = '$user_id'";
                $result = $myDB->getRow($sql);
                $oUser = $myDB->assignRowValues($oUser, $result);
                $oUser->user_type = $result['user_type'];
            }
            
            if($oUser->people_id){
                /*-- load parent person properties --*/
                $oUser = Person::loadChild($oUser);
            }
            
        DEBUG((array)$oUser, 'Initialize User Object:', true, SQL);
        return $oUser;
    }
    
    static public function getFormObject($user_id=NULL,$people_id=NULL){
        $oUser = self::getObject($user_id, $people_id);
		$oUser = DB::assignFormValues($oUser);
		$oUser->active = $_POST[active];
		/*--assign parent properties--*/
		$oUser = DB::assignFormValues($oUser, 'oPerson');
		return $oUser;
    }
    
    static public function saveObject($oUser){
        global $myDB;
        
        /*-- save parent properties in db --*/
        $oUser = Person::saveObject($oUser);

            if($oUser->user_id){  
                $sql = $myDB->generateUpdateSQL($oUser);
                $myDB->runQueryUpdate($sql);
            } else {
                $oUser->active = '1';
                $oUser->password = sha1(trim($oUser->password));
                $sql = $myDB->generateInsertSQL($oUser);
                $oUser->user_id = $myDB->runQueryInsert($sql);
            }
            
        return $oUser;
    }
    
    static private function deleteUser($user_id){
        global $myDB;
        $sql = "DELETE FROM users WHERE user_id = '$user_id'";
        $myDB->runQueryUpdate($sql);
    }
    
    function getObjects($order_by=NULL) {
	
		global $myDB;
    
        $sql = "SELECT user_id 
                FROM users u 
                INNER JOIN people p ON(u.people_id=p.people_id)
                ORDER BY";
            
        $sql .= (!is_null($order_by)) ? $order_by : ' p.last_name, p.first_name';    
        
        $oPager = new Pager(30,"/users.php?mode=list");
        $results = $oPager->getDataSet($sql,'user_id'); 
        $oUsers = array();
        
            foreach($results AS $result){
                $oUsers[] = self::getObject($result['user_id']);
            }
            
        $this->oTemp = $oUsers;
        $this->topPaging = $oPager->drawTopPaging(11);
        $this->bottomPaging = $oPager->drawBottomPaging(11);
    }
	 
	public function updatePassword($user_id=NULL) {
	 	global $myDB;
		
		$password = sha1(trim($_POST['password']));
		$password_confirm = sha1(trim($_POST['password_confirm']));
		
		$oUser = self::getObject($user_id);

        if ($password == $password_confirm) {
            $oUser->password = $password;
            $this->saveObject($oUser);
            $this->success_message = "Password was updated.";
            $this->action = "edit_user";
            $this->renderPage();
        } else {
            $this->error_message = "Your passwords don't match! Please try again.";
            $this->action = "edit_password";
            $this->renderPage();
        } 
	}
	
	static public function getActiveUsers(){
	   global $myDB;
	   $sql = "SELECT user_id 
	           FROM users
	           ORDER BY login_date desc
	           LIMIT 3";
	   
	   return $myDB->select($sql);
	}
}
?>