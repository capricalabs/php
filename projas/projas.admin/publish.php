<?php
/*
 *
 *
 *
 * @author		DWN
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: publish.php,v 1.3 2006-03-30 23:18:32 dan Exp $
 *
 */

include_once ('../conf/global.cfg');
include_once (PROJAS_CONTROL.'cPaperManager.php');

cLogin::logInCheck();

$arSubNav = array (
        "Publishing Q" => "/publish.php?mode=summary&type=5",
        "Published" => "/publish.php?mode=summary&type=1",
        "Search" => "/publish.php?mode=search_papers",
        "Submit(public)" => SUBMISSION_SERVER_PATH."submit.php?mode=publish",
        "Publish" => "/publish.php?mode=publish"
);

$arProcessed = array("Process New" => "/publish.php?mode=publish_process");

    if(Submission::unprocessedExist(true)){
        $arSubNav = array_merge($arProcessed,$arSubNav);
    }

/*-- Initialize Paper Manager --*/
	$myPM = new PaperManager();
    $myPM->action = $_POST["mode"] ;

/*-- Construct Page Data --*/

    $myPM->renderPage(); 
    $sTitle = $myPM->pgtitle;
    $sThirdNav = $myPM->thirdnav;
    $sContent = $myPM->content;
    $sNav = 'Publish Papers';
    $sErrorMessage = $myPM->error_message;
    $sSuccessMessage = $myPM->success_message;
    $sMessage = $myPM->message;
    $sTopPaging = $myPM->topPaging;
    $sBottomPaging = $myPM->bottomPaging;
    $sFormInclude = $myPM->formInclude;
    $sFormAction = $myPM->form_action;
    
include_once (PROJAS_TEMPLATES.$myPM->template);
?>