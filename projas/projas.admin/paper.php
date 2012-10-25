<?php
/*
 *
 *
 *
 * @author		DWN
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: paper.php,v 1.2 2006-03-30 23:18:32 dan Exp $
 *
 */

include_once ('../conf/global.cfg');
include_once (PROJAS_CONTROL.'cPaperManager.php');
include_once (PROJAS_CONTROL.'cPaperSearch.php');

cLogin::logInCheck();

$arSubNav = array (
        "Under Review" => "/paper.php?mode=summary&type=0",
        "Overdue" => "/paper.php?mode=summary&type=4",
        "Accepted" => "/paper.php?mode=summary&type=3",
        "All Papers" => "/paper.php?mode=summary&type=2",
        "Search" => "/paper.php?mode=search_papers",
        "Submit(public)" => SUBMISSION_SERVER_PATH."submit.php"
);

$arProcessed = array("Process New" => "/paper.php?mode=process");
$arMyPapers = array("My Papers" => "/paper.php?mode=summary&type=6");

    if(Submission::unprocessedExist() && cLogin::hasPermission()){
        $arSubNav = array_merge($arProcessed,$arSubNav);
    } elseif (cLogin::hasPermission() === false){
        $arSubNav = array_merge($arMyPapers,$arSubNav);
    }

/*-- Initialize Paper Manager --*/
	$myPM = new PaperManager();
    $myPM->action = $_POST["mode"] ;

/*-- Construct Page Data --*/

    $myPM->renderPage(); 
    $sTitle = $myPM->pgtitle;
    $sThirdNav = $myPM->thirdnav;
    $sContent = $myPM->content;
    $sNav = 'Review Papers';
    $sErrorMessage = $myPM->error_message;
    $sSuccessMessage = $myPM->success_message;
    $sMessage = $myPM->message;
    $sTopPaging = $myPM->topPaging;
    $sBottomPaging = $myPM->bottomPaging;
    $sFormInclude = $myPM->formInclude;
    $sFormAction = $myPM->form_action;
    
include_once (PROJAS_TEMPLATES.$myPM->template);
?>