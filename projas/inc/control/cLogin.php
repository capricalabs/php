<?php
/*
 *
 *
 *
 * @author		DWN<dogtowngeek@gmail.com>
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: cLogin.php,v 1.2 2006-02-10 02:15:49 dan Exp $
 *
 *
 */
 
include_once (PROJAS_CONTROL.'cUsers.php');

class cLogin extends cMasterController{
    
    static function logInCheck(){
        
        if(isset($_SESSION["user_id"])){
            return true;
        } else {
            session_unset(); 
            session_destroy();
            GOTO('/login.php');
        }
    }
    
    function checkLogin($username, $password) {
        
        global $myDB;

        $sql = "SELECT user_id
                FROM users 
                WHERE username='$username' AND 
                password = '".sha1($password)."'";
                
        $user = $myDB->getRow($sql);

        if ($user) {
            $oUser = User::getObject($user['user_id']);
            $_SESSION["user_id"] = $oUser->user_id;
            $_SESSION["user_type_id"] = $oUser->user_type_id;
            $_SESSION["people_id"] = $oUser->people_id;
            $_SESSION['first_name'] = $oUser->first_name;
            $_SESSION['last_name'] = $oUser->last_name;
            $_SESSION['last_login'] = $oUser->login_date;
            $oUser->login_date = 'NOW()';
            User::saveObject($oUser);
            header("Location: /paper.php?mode=login");
        } else {
            $this->error_message = "Your username or password are incorrect. Please try again.";
        }
    }
    
    function logout() {
        session_unset(); 
        session_destroy();
        $this->success_message = "You have logged out.";
    }
    
    /*-- conditions access based on admin or editor user-type --*/
    static function hasPermission($oPaper=NULL){
        if(!is_object($oPaper)){
            return ($_SESSION['user_type_id'] == '1') ? true : false; 
        } else {
            return ($_SESSION['user_type_id'] == '1' || $oPaper->editor_user_id == $_SESSION['user_id']) ? true : false;
        }
    }
}
?>