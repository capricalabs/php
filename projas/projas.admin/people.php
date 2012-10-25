<?php
/*
 *
 *
 *
 * @author		Lauren Bradford
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: people.php,v 1.2 2006-02-08 17:11:35 dan Exp $
 *
 *
 */

include_once ('../conf/global.cfg');
include_once (PROJAS_CONTROL.'/cPerson.php');

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
	$myPerson = new Person();
    $myPerson->action = $_POST[mode];

/*-- Construct Page Data --*/

    $myPerson->renderPage(); 
    $sContent = $myPerson->content;
    $sSuccessMessage = $myPerson->success_message;
    $sMessage = $myPerson->message;
    $sErrorMessage = $myPerson->error_message;
    $sFormInclude = $myPerson->formInclude;
    $sTopPaging = $myPerson->topPaging;
    $sBottomPaging = $myPerson->bottomPaging;
    $sTitle = $myPerson->pgtitle;
    $sNav = 'People';

include_once (PROJAS_TEMPLATES.'common.tmpl');
?>