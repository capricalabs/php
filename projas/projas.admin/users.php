<? 	

/*
 *
 *
 *
 * @author		DWN<dogtowngeek@gmail.com>, LB
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: users.php,v 1.2 2006-02-15 01:32:58 dan Exp $
 *
 *
 *
 */

include_once ('../conf/global.cfg');
include_once (PROJAS_CONTROL.'/cUsers.php');

cLogin::logInCheck();

$arSubNav = array (
    "All People" => "/people.php?mode=list",
    "All Users" => "/users.php?mode=list",
    "Search" => "/people.php"
);

$arAccess = array(
        "Add Person" => "/people.php?mode=add",
        "Add User" => "/users.php?mode=add_user"
);

    if(cLogin::hasPermission()){
        $arSubNav = array_merge($arAccess,$arSubNav);
    }

/*-- Initialize Paper Manager --*/
	$myUser = new User();
    $myUser->action = $_POST[mode];

/*-- Construct Page Data --*/

    $myUser->renderPage(); 
    $sContent = $myUser->content;
    $sSuccessMessage = $myUser->success_message;
    $sMessage = $myUser->message;
    $sErrorMessage = $myUser->error_message;
    $sFormInclude = $myUser->formInclude;
    $sTopPaging = $myUser->topPaging;
    $sBottomPaging = $myUser->bottomPaging;
    $sTitle = $myUser->pgtitle;
    $sNav = 'Users';
    $sFormAction = $myUser->form_action;
    
include_once (PROJAS_TEMPLATES.'common.tmpl');
?>