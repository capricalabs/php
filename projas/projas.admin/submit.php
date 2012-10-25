<?php

/*
 *
 *
 *
 * @author		DWN<dogtowngeek@gmail.com>
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: submit.php,v 1.1.1.1 2006-02-08 02:24:33 dan Exp $
 *
 *
 */

include_once ('../conf/global.cfg');
include_once (PROJAS_CONTROL.'cSubmission.php');

/*-- enables us to restore objects from session --*/
    if($_POST[delay_session_start]){
        session_start();
    }
    
	$mySub = new Submission();
	$mode = (!$_POST[mode]) ? 'step1' : $_POST[mode] ;
    $mySub->action = $mode;
    $mySub->renderPage();
    
//$sNav = 'Submit Paper';
$sErrorMessage = $mySub->error_message;
$sSuccessMessage = $mySub->success_message;
$sMessage = $mySub->message;

include_once (PROJAS_TEMPLATES.$mySub->template);
?>