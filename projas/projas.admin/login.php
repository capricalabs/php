<? 	

/*
 *
 * $source$
 *
 *
 * @author		Lauren Bradford, DWN
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: login.php,v 1.1.1.1 2006-02-08 02:24:33 dan Exp $
 *
 *
 */


include_once ('../conf/global.cfg');

    $myLogin = new cLogin();

        if ($_POST[mode] == "login") {
            $myLogin->checkLogin($_POST["username"],$_POST["password"]);
        } elseif ($_POST[mode] == "logout"){
            $myLogin->logout();
        } elseif ($_POST[mode] == "expire"){
            $myLogin->error_message = "Your session has expired.  Please log in again";
    
        }
    
    $sErrorMessage = $myLogin->error_message;
    $sSuccessMessage= $myLogin->success_message;
    $sFormInclude = 'login';
    
include_once (PROJAS_TEMPLATES.'common.tmpl');
?>