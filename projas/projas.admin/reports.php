<?php
/*
 *
 *
 *
 * @author		DWN
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: reports.php,v 1.2 2006-03-30 23:18:32 dan Exp $
 *
 */

include_once ('../conf/global.cfg');
include_once (PROJAS_CONTROL.'cPaperManager.php');
include_once (PROJAS_CONTROL.'cReports.php');

cLogin::logInCheck();

$arSubNav = array (
        "Editor Activity" => "/reports.php?mode=reports&type=0",
   //     "Editor Keyword" => "/reports.php?mode=reports&type=2",
        "Editor Status" => "/reports.php?mode=reports&type=3"	//,
   //     "Editors' Status" => "/reports.php?mode=reports&type=4",
   //     "Annual Published" => "/reports.php?mode=reports&type=5",
   //     "Sub-History" => "/reports.php?mode=reports&type=6",
   //     "Acceptance Rate" => "/reports.php?mode=reports&type=7",
   //     "Sub-Region" => "/reports.php?mode=reports&type=8",
   //     "Region-Accepted" => "/reports.php?mode=reports&type=9",
   //     "Sub-Status Keyword" => "/reports.php?mode=reports&type=10",
   //     "Review Time" => "/reports.php?mode=reports&type=11",
   //     "Editorial Staff" => "/reports.php?mode=reports&type=12"
);

/*-- Initialize Paper Manager --*/
    $myPM = new paperManager();
    $myPM->action = $_POST["mode"] ;

/*-- Construct Page Data --*/

    $myPM->renderPage(); 
    $sTitle = $myPM->pgtitle;
    $sThirdNav = $myPM->thirdnav;
    $sContent = $myPM->content;
    $sNav = 'Reports';
    $sErrorMessage = $myPM->error_message;
    $sSuccessMessage = $myPM->success_message;
    $sTopPaging = $myPM->topPaging;
    $sBottomPaging = $myPM->bottomPaging;
    $sFormInclude = $myPM->formInclude;
    $sFormAction = $myPM->form_action;
    
include_once (PROJAS_TEMPLATES.$myPM->template);
?>