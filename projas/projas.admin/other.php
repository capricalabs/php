<?php
/*
 *
 *
 *
 * @author		DWN
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: other.php,v 1.5 2006-04-23 14:57:32 dan Exp $
 *
 */

include_once ('../conf/global.cfg');
include_once (PROJAS_CONTROL.'cEmail.php');
include_once (PROJAS_CONTROL.'cSubmission.php');

cLogin::logInCheck();

$arSubNav = array (
        "Edit E-mails" => "/other.php?mode=email",
        "XML Feed" => "",
        "Submissions" => "/other.php?mode=submissions"
);

    switch($_POST['mode']){
        case 'email':
            
            switch ($_POST['action']){
                case 'save':
                    $oEmail = cEmail::getFormObject($_POST['email_id']);
                    $oEmail = cEmail::saveObject($oEmail);
                    $sSuccessMessage = "E-mail has been saved.";
                    $sFormInclude = 'select_email';
                    break;
                    
                case 'edit':
                    $oEmail = cEmail::getObject(NULL, $_POST['email_id']);
                    $sFormInclude = 'edit_email';
                    break;
                    
                default:

                    $sFormInclude = 'select_email';
            }

            break;
            
        case 'submissions':
            
             $sFormInclude = 'process';
            $sTitle = 'All Submissions';
            $sql .= "SELECT submission_id 
                     FROM submissions 
                     ORDER BY submission_id DESC";
                
            $pager_url = "/other.php?mode=submissions";
            
            $oPager = new Pager(10,$pager_url);
            $results = $oPager->getDataSet($sql,'submission_id',true);    
                if(count($results) && is_array($results)){
                    $oSubmissions = array();
                        foreach($results AS $result) {
                            $oSubmissions[] = Submission::loadObject($result["submission_id"]);
                        }
                }

            $sTopPaging = $oPager->drawTopPaging(11);
            $sBottomPaging = $oPager->drawBottomPaging(11);
            break;
            
        default:
    }

/*-- Construct Page Data --*/

    $sNav = 'Other Stuff';
    $sErrorMessage = $myPM->error_message;
    $sMessage = $myPM->message;
    $sFormAction = $myPM->form_action;
    
include_once (PROJAS_TEMPLATES.'common.tmpl');
?>