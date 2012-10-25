<?php
/*
 *
 *
 * @author       DWN<dogtowngeek@gmail.com>
 * @copyright    (c) 2005 PROJAS
 * @version      $Id: cPaperManager.php,v 1.34 2006-04-23 14:57:32 dan Exp $
 *
 *
 */

include_once (PROJAS_CONTROL.'cEmailFactory.php');
include_once (PROJAS_CONTROL.'cPaper.php');
include_once (PROJAS_CONTROL.'cAuthor.php');
include_once (PROJAS_CONTROL.'cReviewer.php');
include_once (PROJAS_CONTROL.'cMedia.php');
include_once (PROJAS_CONTROL.'cSubmission.php');
include_once (PROJAS_CONTROL.'cPublish.php');
include_once (PROJAS_CONTROL.'cPublishFactory.php');

class PaperManager extends cMasterController {

    /*-- private properties --*/
    private $searchresults = array();

    /*-- paper collection --*/
    public $papers = array();

    public function renderPage() {

        global $myDB;

        switch($this->action) {
            
            case "delete_paper":
                
                    if(Paper::deletePaper($_POST['paper_id']) === false){
                        $this->error_message = 'Paper can not be deleted, it is associated to an author, reviewer, or media item.';
                        $this->action = "paper_details";
                        $this->renderPage();
                    } else {
                        $this->success_message = 'Paper has been deleted from system';
                    }
                    
                break;
                
            case "delete_media":

                $oMedia = Media::getMediaItem($_POST[media_item_id]);
                Media::deleteMediaItem($oMedia);
                $this->success_message = "Media Item has been deleted.";
                $this->action = ($_POST['is_published'])? 'publish_details' : 'paper_details';
                $this->renderPage();
                break;

            case "save_media":

                $oMedia = Media::getFormMediaObject($_POST[media_item_id]);
                $oMedia = Media::saveMedia($oMedia);

                    if(!$oMedia->media_item_id){
                        global $arUploadErrors;
                        $this->error_message = ($oMedia->transfer_error) ? $arUploadErrors[$oMedia->transfer_error] : 'No file or url were submitted.';
                        $this->action = 'edit_media';
                        $this->oTemp = $oMedia;
                    } else {
                        $this->success_message = "Media Item has been saved.";
                        $this->action = ($_POST['is_published']) ? 'publish_details' : 'paper_details';
                    }

                $this->renderPage();
                break;

            case "edit_media":

                if(!is_object($this->oTemp)){
                    $oMedia = Media::getMediaItem($_POST[media_item_id]);
                    $this->oTemp = $oMedia;
                }
                
                $this->formInclude='edit_media';
                break;

            case "add_author":

                if (!strlen(trim($_POST[person_first_name])) || !strlen(trim($_POST[person_last_name]))) {
                    $this->action = "view_paper_details";
                    $this->error_message = "You must enter the author's first and last name.";
                    $this->action = ($_POST['is_published']) ? 'publish_details' : 'paper_details';
                    $this->renderPage();
                    break;
                }

                $this->submode = 'add_author';
                $this->action = 'choose_person';
                $this->renderPage();
                break;

            case "add_reviewer":

                if (!strlen(trim($_POST[person_first_name])) || !strlen(trim($_POST[person_last_name]))) {
                    $this->action = "view_paper_details";
                    $this->error_message = "You must enter the reviewer's first and last name.";
                    $this->action = 'paper_details';
                    $this->renderPage();
                    break;
                }

                $this->submode = 'add_reviewer';
                $this->action = 'choose_person';
                $this->renderPage();
                break;

            case "choose_person":

                $this->formInclude='choose_person';
                $this->form_action = ($_POST['is_published']) ? '/publish.php' : '/paper.php';

                //$this->pgtitle = 'Manage Person';
                $this->message = "Please associate existing person, or create new";
                break;

            case "delete_reviewer":

                Reviewer::deleteReviewer($_POST[reviewer_id]);
                $this->success_message = "Reviewer has been deleted.";
                $this->action = 'paper_details';
                $this->renderPage();
                break;

            case "delete_author":

                Author::deleteAuthor($_POST[author_id]);
                $this->success_message = "Author has been deleted.";
                $this->action = ($_POST['is_published']) ? 'publish_details' : 'paper_details';
                $this->renderPage();
                break;

            case "ask_editor":

                $this->message = "Review, modify, and send Editor E-mail Request";
                $this->pgtitle = 'Editor Request<br>';
                $this->formInclude='email';
                $oPaper = Paper::getObject($_POST[paper_id]);
                $oPaper->editor_user_id = $_POST[editor_user_id];
                $this->oTemp = $oPaper;
                $oEmail = new cEmailFactory();
                $oEmail->askEditor($oPaper);
                $this->oEmail = $oEmail;
                break;

            case "assign_editor":
            
                $this->message = "Please choose an editor to assign to paper $_POST[paper_id]";
                $this->formInclude='assign_editor';
                break;

            case "process":

                $this->formInclude='process';
                    if(!$oSubmissions = Submission::getSubmissions()){
                        $this->message = 'There are no submissions in the Q.';
                    }

                $this->oTemp = $oSubmissions;
                $this->pgtitle = 'Process for Review<br>';
                break;

            case "person_chosen":

                if($_POST['submode'] == "add_reviewer"){
                    /*-- existing papers --*/
                    if($_POST['people_id']){
                        $this->action = "update_reviewer";
                        $this->success_message = 'Reviewer has been added to paper';
                        $this->processData();
                        break;
                    } else {
                        $this->action = 'edit_reviewer';
                        $this->renderPage();
                    }

                } elseif ($_POST['submode'] == "process_author"){
                    /*-- new papers --*/
                    if($_POST[people_id]){
                        $this->success_message = 'Revise to latest data, or revert to legacy for this Person.';
                        
                            if(!isset($_POST['people_id']) || !isset($_POST['people_id'])){
                                $this->error_message = 'Person or Author Submission data are missing for merge.';
                                break;
                            }
                        
                        $oPerson = Person::getObject($_POST['people_id']);
                        $oSubAuthor = Submission::getSubAuthor($_POST['submission_author_id']);
                        
                        $this->oTemp = Person::processMerge($oPerson,$oSubAuthor);
                        $this->formInclude='author_merge';
                        break;
                    } else {
                        $this->action = 'edit_author';
                        $this->submode = 'process_author';
                        $this->renderPage();
                    }

                } elseif ($_POST['submode'] == "add_author"){
                    /*-- existing published papers --*/
                    if($_POST['is_published']){
                        /*-- populate post super array for update --*/
                            $_POST['published_first_name'] = $_POST['person_first_name'];
                            $_POST['published_middle_name'] = $_POST['person_middle_name'];
                            $_POST['published_last_name'] = $_POST['person_last_name'];
                        if($_POST[people_id]){
                            $this->action = "update_author";
                            $this->success_message = 'Author has been added to paper';
                            $this->processData();
                        } else {
                            $_POST['first_name'] = $_POST['person_first_name'];
                            $_POST['middle_name'] = $_POST['person_middle_name'];
                            $_POST['last_name'] = $_POST['person_last_name'];
                            $this->action = 'publish_edit_author';
                            $this->submode = 'existing_paper';
                            $this->renderPage();
                        }
                    } else {
                        /*-- existing review papers --*/
                        if($_POST[people_id]){
                            $this->action = "update_author";
                            $this->success_message = 'Author has been added to paper';
                            $this->processData();
                        } else {
                            $this->action = 'edit_author';
                            $this->submode = 'existing_paper';
                            $this->renderPage();
                        }
                    }
                }
                break;
            
            case "merge_person":
                
                $this->action = "update_author";
                $this->success_message = 'Author has been added to paper';
                $this->processData();
                break;
                
            case "create_reminder":

                $this->pgtitle = 'Reviewer E-mail Reminder';
                $oPaper = Paper::getObject($_POST["paper_id"]);
                $this->formInclude='email';
                $oEmail = new cEmailFactory();
                $oEmail->remindReviewer($oPaper);
                $this->oEmail = $oEmail;
                break;

            case "edit_paper":

                $this->formInclude='edit_paper';
                array_push($this->papers, Paper::getObject($_POST[paper_id]));
                break;

            case "edit_author":

                $this->formInclude='edit_author';

                    /*--  addding new author/person   --*/
                    if(!$oAuthor->author_id && !$_POST[author_id]){
                        $oAuthor = Submission::subToAuthor();
                    } else {
                        $oAuthor = Author::getObject($_POST[author_id]);
                    }

                $this->oTemp = $oAuthor;
                break;

            case "edit_reviewer":

                $this->formInclude='edit_reviewer';
                $oReviewer = Reviewer::getObject($_POST["reviewer_id"]);
                $this->oTemp = $oReviewer;
                    /*--  addding new reviewer/person   --*/
                    if(!$oReviewer->reviewer_id){
                        $oReviewer->paper_id = $_POST[paper_id];
                        $oReviewer->first_name = $_POST[person_first_name];
                        $oReviewer->middle_name = $_POST[person_middle_name];
                        $oReviewer->last_name = $_POST[person_last_name];
                    }
                break;

            case "email_contact_author":

                $oPaper = Paper::getObject($_POST[paper_id]);
                //$this->pgtitle= 'E-mail Author';
                $this->formInclude='email';
                $oEmail = new cEmailFactory();
                $oEmail->contactAuthor($oPaper);
                $this->oEmail = $oEmail;
                break;
            
            case "send_attachments":
                
                $oPaper = Paper::getObject($_POST[paper_id]);
                    if(!Media::hasPublicAttachments($oPaper)){
                        $this->error_message = 'E-mail can not be compiled. Please be sure that there is at least 1 public media item.';
                        $this->action = 'paper_details';
                        $this->renderPage();
                        break;
                    }
                $this->oTemp = $oPaper;
                $this->formInclude='email';
                $oEmail = new cEmailFactory();
                $oEmail->sendAttachments($oPaper);
                $this->oEmail = $oEmail;
                break;
                
            case "summary":

                $this->formInclude='paper_summary';
                $this->getPaperListings();
                break;

            case "paper_details":
                
                $this->pgtitle = "Review Paper<br>";

                $oPaper = Paper::getObject($_POST[paper_id]);
                
                    if(!$oPaper->paper_id) {
                        $this->error_message = "Sorry, no paper with ID #$_POST[paper_id] was found in system.";
                        break;
                    } elseif (cLogin::hasPermission($oPaper) === false){
                        $this->error_message = "You do not have access to this paper.";
                        break;
                    }
                    
                array_push($this->papers, $oPaper);
                $this->thirdnav = vAdmin::drawBreadCrumb($oPaper);
                $this->formInclude='paper_details';
                break;
            
            case "submission_details":
                
                $this->pgtitle = "Submission Details<br>";
                $oSubmission = Submission::loadObject($_POST['submission_id']);
                array_push($this->papers, $oSubmission);
                $this->thirdnav = vAdmin::drawBreadCrumb($oSubmission);
                $this->formInclude='submission_details';

                break;
/*-- PUBLISHING STUFF START --*/
            
            case "publish":
            
                $this->formInclude='publish';
                break;
            
            case "publish_transfer":
                
                /*-- flag db, to sync filesystem w/remote live host --*/
                $sql = "SELECT max(published_log_id) AS published_log_id
                        FROM published_log";
                $result = $myDB->getRow($sql);
                
                $sql = "UPDATE published_log
                        SET is_active = '1',
                            is_transfered = '0'
                        WHERE
                            published_log_id = '$result[published_log_id]'";
                
                $myDB->runQueryUpdate($sql);
                $this->formInclude='publish';
                $this->success_message = "Site will be published to the live server within the next 5 minutes.";
                break;
                
            case "publish_generate":
            
                $oPublishFactory = new cPublishFactory();
                $oPublishFactory->commencePublish();
                $this->formInclude='publish';
                
                    if($oPublishFactory->success){                    
                        $this->success_message = "Site regeneration successfully executed in ".$oPublishFactory->execute_time." seconds.";    
                        $this->success_message .= "<br/>To preview new site, <a href=\"http://jair.public.dtd.la/\" onclick=\"window.open(this.href);return false;\">click here</a>.";
                    } else {
                        $this->error_message = "An error occured during the publishing process:\r\n";
                        $this->error_message .= $oPublish->error ;
                    }
                break;
                
            case "publish_edit_author":
                
                $this->formInclude='publish_edit_author';
                $oAuthor = Author::getFormObject($_POST[author_id]);
                $this->oTemp = $oAuthor;
                break;
                
            case "publish_process":
                
                $this->formInclude='publish_process';
                    if(!$oSubmissions = Submission::getSubmissions(true)){
                        $this->message = 'There are no publish submissions in the Q.';
                    }

                $this->oTemp = $oSubmissions;
                $this->pgtitle = 'Process Publications<br>';
                break;
                
            case "publish_details":
                
                $this->pgtitle = "Publish Paper<br>";
                $oPubPaper = cPublish::getObject($_POST[paper_id]);
                array_push($this->papers, $oPubPaper);
                $this->thirdnav = vAdmin::drawBreadCrumb($oPubPaper);
                $this->formInclude='publish_details';
                break;
            
            case "publish_paper":
                
                $this->success_message = "Paper $_POST[paper_id] will be published on the local published site! Now send an e-mail notification to the authors.";
                $oPubPaper = cPublish::getObject($_POST[paper_id]);
                $oPubPaper->is_active='1';
                $oPubPaper->publish_date = 'NOW()';
                cPublish::saveObject($oPubPaper);
                $this->formInclude='email';
                $oEmail = new cEmailFactory();
                $oEmail->publishAlert($oPubPaper);
                $this->oEmail = $oEmail;
                break;
            
            case "unpublish_paper":
                
                $this->success_message = "Paper $_POST[paper_id] will be removed from the local publisehd site!<br/>Syncronize with host mirror, to make the changes official.";
                $oPubPaper = cPublish::getObject($_POST[paper_id]);
                $oPubPaper->is_active='NULL';
                $oPubPaper->publish_date = 'NULL';
                cPublish::saveObject($oPubPaper);
                $this->action = 'publish_details';
                $this->renderPage();
                break;
            
            case "publish_edit_paper":
            
                $this->formInclude='publish_edit_paper';
                array_push($this->papers, cPublish::getObject($_POST[paper_id]));
                break;
                
/*-- PUBLISHING STUFF END --*/

            case "update_reviewer":
            
                $this->success_message = 'Reviewer has been updated';
                $this->processData();
                break;

            case "search_papers":

                $this->formInclude='search_papers';
                $this->processData();
                break;

            case "reports":

                switch ($_POST['type']) {

	                case 0:

                        $this->pgtitle = 'Editor Activity Report<br>';
                        $this->formInclude='report_editor_activity';
	                    $sql = Report::editorActivity();
                        $this->searchresults = $myDB->select($sql);
                        $this->reports = Report::compile($this->searchresults,'activity');
	                    break;

	                case 2:

	                    Report::editorKeyWord();
	                    break;

	                case 3:

                        $this->formInclude='report_choose_editor';
                        $this->processData();
	                    break;

	                default:
	                    break;

            	}

                break;

            case "update_author":
            case "confirm_editor":
            case "unconfirm_editor":
            case "update_paper":
            case "email_send":
            case "review_status":
            case "record_decision":
            case "reject_submission":
            case "accept_submission":
            case "update_published_paper":
            
                $this->processData();
                break;

            default:
                    if($this->action == "login"){
                        $this->message= "Welcome back $_SESSION[first_name]! Your last login was on ".vAdmin::formatDate($_SESSION['last_login'],false,true,true).".";
                    }

                $this->formInclude='home';
                break;
        }
    }

    public function processData() {

        global $myDB;

        switch($this->action) {

	        case "reports":
	        
	            if($_POST['submode'] == "submit") {
                    $this->pgtitle = 'Editor Status Report<br>';
                    $this->formInclude='report_editor_status';
 	                $sql = Report::editorStatus($_POST[people_id]);
                    $this->searchresults = $myDB->select($sql);
                    $this->oReport = Report::compile($this->searchresults,'status');
                }
                
                break;

            case "search_papers":

                if($_POST['submode'] == "submit") {

                    $sql = cSearchPapers::buildQuery($is_publish);
                    $this->pgtitle = 'Search Papers - Results';
                        if($is_publish){
                            $this->formInclude='publish_summary';
                        } else {
                            $this->formInclude='paper_summary';
                        }
                    $pager = new Pager(10,"/paper.php?mode=search_papers&submode=submit");
                    $this->searchresults = $pager->getDataSet($sql,'p.paper_id');    
                    $this->collectPapers($is_publish); 
                    $this->topPaging = $pager->drawTopPaging(11);
                    $this->bottomPaging = $pager->drawBottomPaging(11);
                }
                break;

            case "unconfirm_editor":

                $oPaper = Paper::getObject($_POST["paper_id"]);
                $oPaper->date_editor_accepted = 'NULL';
                Paper::saveObject($oPaper);
                $this->success_message = 'The paper editor confirmation has been reset';
                $this->action = 'paper_details';
                $this->renderPage();
                break;

            case "confirm_editor":

                $oPaper = Paper::getObject($_POST["paper_id"]);
                $oPaper->date_editor_accepted = 'NOW()';
                Paper::saveObject($oPaper);
                $this->success_message = 'The paper editor has been set as "Confirmed".';
                $this->action = 'paper_details';
                $this->renderPage();
                break;

            case "accept_submission":

                $oPaper = Paper::getObject();
                $oSubmission = Submission::loadObject($_POST["submission_id"]);
                $oSubmission->date_acknowledged = 'NOW()';
                $oSubmission->acknowledged = '1';
                $oPaper->submission_id = $oSubmission->submission_id;
                $oPaper->paper_id = $_POST['paper_id'];
                                      
                    /*-- build paper record --*/
                    if(!$oSubmission->paper_id){
                        $oPaper = Paper::saveObject($oSubmission);
                        $oSubmission->paper_id = $oPaper->paper_id;
                        Submission::saveObject($oSubmission);
                        Media::processMedia($oPaper);
                    }
                    
                    /*-- submission is publication --*/
                    if($oSubmission->is_publication){
                        $oPubPaper = cPublish::getObject($oSubmission->paper_id);
                            if(!$oPubPaper->published_paper_id){
                                Submission::saveObject($oSubmission);
                                $oSubmission->abstract_published = $oSubmission->abstract;
                                $oSubmission->title_published = $oSubmission->title;
                                $oSubmission->published_submission_id = $oSubmission->submission_id;
                                $oPubPaper = cPublish::saveObject($oSubmission,true);
                                $oSubmission->paper_id = $oPaper->paper_id;
                                Submission::saveObject($oSubmission);
                                Media::processMedia($oPaper,true);
                            }
                    }

                Submission::getUA_Authors($oPaper);

                $this->action = "choose_person";
                $this->submode = "process_author";
                $this->renderPage();
                break;

            case "assign_editor":
            case "choose_person":
            case "process":

                break;

            case "review_status":

                $oReviewer = Reviewer::getObject($_POST["reviewer_id"]);

                if($_POST[status]){
                    $oReviewer->date_received = 'NULL';
                } else {
                   $oReviewer->date_received = 'NOW()';
                }

                Reviewer::saveObject($oReviewer);
                $this->success_message = 'Reviewer status has been updated.';
                $_POST[paper_id] = $oReviewer->paper_id;
                $this->action = 'paper_details';
                $this->renderPage();
                break;

            case "email_send":
                
                $this->action = 'paper_details';
                $send_attachments = false;
                
                if($_POST['email_mode'] == "publish_email"){
                
                    $this->success_message = 'Author Notification sent. Now preview locally published site,<br/> and Syncronize with Mirror Host when ready.';
                    $this->action = 'publish_details';
                    
                } elseif ($_POST['email_mode'] == "send_attachments"){

                    $oPaper = Paper::getObject($_POST["paper_id"]);
                    $this->success_message = 'Files have been sent to Reviewer(s).';
                    $send_attachments = true;
                    
                } elseif ($_POST['email_mode'] == "remind_reviewer") {

                    $oReviewer = Reviewer::getObject($_POST["reviewer_id"]);
                    $oReviewer->total_reminders_sent = $oReviewer->total_reminders_sent + 1;
                    $oReviewer->date_last_reminder = 'NOW()';
                    Reviewer::saveObject($oReviewer);
                    $this->success_message = 'Reminder was sent.';
                
                } elseif ($_POST['email_mode'] == "contact_author"){
                
                    $this->success_message = 'E-mail acknowledgement was sent.';
                    
                } else {
                
                    $oPaper = Paper::getObject($_POST["paper_id"]);
                    $oPaper->editor_user_id = $_POST["user_id"];
                    $oPaper->date_editor_assigned = 'NOW()';
                    $oPaper->date_editor_accepted = 'NULL';
                    Paper::saveObject($oPaper);
                    $send_attachments = true;
                    $this->success_message = 'E-mail request was sent.';
                }

                $mail = new htmlMimeMail5();
                $mail->setFrom($_POST['from']);
                $mail->setCc($_POST['cc']);
                $mail->setSubject($_POST['subject']);
                $mail->setPriority('high');
                $mail->setText($_POST['body']);
                $arTo = explode(",",$_POST['to']);
                
                    /*-- attach any files --*/
                    if ($send_attachments) {
                        if(count($oPaper->media)){
                            foreach($oPaper->media AS $oMedia){
                                if($oMedia->is_public){
                                    $file = PROJAS_MEDIA . '/' . $_POST[paper_id].'/'. Media::getFilePath($oMedia) ;
                                    $mail->addAttachment(new fileAttachment($file));
                                }
                            }
                        }
                    }
                
                $mail->send($arTo);
                $this->renderPage();
                break;

            case "record_decision":

                $oPaper = Paper::getObject($_POST["paper_id"]);
                $oPaper->decision = $_POST["decision"];
                $oPaper->decision_date = 'NOW()';
                Paper::saveObject($oPaper);
                $this->success_message = 'New Paper Decision Status has been set.';
                $this->action = 'paper_details';
                $this->renderPage();
                break;

            case "reject_submission":

                $oSubmission = $this->rejectSubmission();
                $this->action = ($_POST['is_published']) ? 'publish_process' : 'process';
                $this->success_message = "Submission #$oSubmission->submission_id has been deleted";
                $this->renderPage();
                break;

            case "update_author":
                
                $this->success_message = 'Author has been updated';
                $oAuthor = Author::getFormObject($_POST["author_id"],$_POST["people_id"]);
                
                    if($_POST['action'] == "merge"){
                        $oAuthor = $this->process_merge();
                    }
                    
                $oAuthor->submission_author_id = $_POST['submission_author_id'];
                    /*-- if we're associating author to existing person, pre-pop the published names --*/
                    if($_POST['people_id'] && !$_POST['is_published']){
                        $oAuthor->published_first_name = $oAuthor->first_name;
                        $oAuthor->published_middle_name = $oAuthor->middle_name;
                        $oAuthor->published_last_name = $oAuthor->last_name;
                    }
  
                Author::saveObject($oAuthor);

                if ($_POST[submode]=="process_author") {
                    /*-- process remaining authors --*/
                    $oPaper = Paper::getObject($_POST["paper_id"]);
                    $oPaper->submission_id = $_POST['submission_id'];

                        if (Submission::getUA_Authors($oPaper)) {
                            $this->action = "choose_person";
                            $this->submode = "process_author";
                            $this->renderPage();
                            break;
                        } else {
                            if($_POST['is_published']){
                                $this->action = "publish_details";
                                unset($this->success_message);
                                $this->message = "You have proccessed the paper's authors. Now verify the paper data before publishing.";
                            } else {
                                $this->action = "edit_paper";
                                $this->submode = "process_paper";
                                unset($this->success_message);
                                $this->message = "You have proccessed the paper's authors. Now confirm the paper data";
                            }
                            
                            $this->renderPage();
                        }
                        
                } else {
                    $this->action = ($_POST['is_published']) ? 'publish_details' : 'paper_details';
                    $this->renderPage();
                }
                break;
            
            case "update_published_paper":
                $oPubPaper = cPublish::getFormObject($_POST["paper_id"]);
                cPublish::saveObject($oPubPaper);
                $this->success_message = 'Paper has been updated.';
                $this->action = 'publish_details';
                $this->renderPage();
                break;
                
            case "update_paper":

                $oPaper = Paper::getFormObject($_POST["paper_id"]);
                Paper::saveObject($oPaper);

                if ($_POST[submode] == "process_paper") {
                    $this->action = "email_contact_author";
                } else {
                    $this->success_message = 'Paper has been updated.';
                    $this->action = 'paper_details';
                }

                $this->renderPage();
                break;

            case "update_reviewer":

                $oReviewer = Reviewer::getFormObject($_POST["reviewer_id"],$_POST["people_id"]);
                Reviewer::saveObject($oReviewer);
                $this->action = 'paper_details';
                $this->renderPage();
                break;

            default:
        }
    }

    private function rejectSubmission() {

        $oSubmission = Submission::loadObject($_POST[submission_id]);
        $oSubmission->acknowledged = '1';
            foreach($oSubmission->authors AS $oAuthor) {
                $oAuthor->acknowledged = '1';
            }
            
        Submission::saveObject($oSubmission);
        return $oSubmission;
    }

    static function getEditors() {

        global $myDB;

        $editor_options = '';
        $results = $myDB->select("SELECT DISTINCT u.user_id,p.first_name,p.last_name,p.email1
                                  FROM users u
                                  INNER JOIN people p ON u.people_id = p.people_id AND
                                  u.active=1
                                  ORDER BY p.last_name, p.first_name");

            foreach($results AS $editor) {
                $editor_options .= "<option value=\"" . $editor["user_id"] . "\">" . $editor["last_name"] .
                                        ",&nbsp;" . $editor["first_name"] . "&nbsp;(" . $editor["email1"] . ")</option>\n";
            }

        return $editor_options;
    }
    
    private function collectPapers($is_publish=false) {
        
        if(is_array($this->searchresults) && count($this->searchresults)) {
            foreach($this->searchresults as $paper) {
                if($is_publish){
                    $oPaper = cPublish::getObject($paper["paper_id"]);
                } else {
                    $oPaper = Paper::getObject($paper["paper_id"]);
                }
                array_push($this->papers,$oPaper);
            }
        }
    }

    private function getPaperListings() {
        global $myDB;
        $is_publish = false;
        
        $sql = "SELECT DISTINCT p.paper_id
                FROM papers p 
                LEFT OUTER JOIN users u ON p.editor_user_id= u.user_id 
                LEFT OUTER JOIN people pe ON pe.people_id = u.people_id
                LEFT OUTER JOIN published_papers pp ON p.paper_id=pp.paper_id";

            switch($_POST[type]) {
                
                case 1:
                /*-- published papers --*/
                    $this->formInclude='publish_summary';
                    $this->pgtitle ='Published Papers';
                    $sql .= " WHERE pp.is_active ='1'";
                    $is_publish = true;
                    break;
                    
                /*-- no decisions --*/
                case 0:
    
                    $this->pgtitle ='Under Review';
                    $sql .= " WHERE p.decision IS NULL";
                    break;
    
                /*-- accepted --*/
                case 3:
    
                    $this->pgtitle ='Accepted, Unpublished Papers';
                    $sql .= " WHERE p.decision='1' AND 
                                NOT EXISTS(SELECT pp.publish_date 
                                FROM published_papers pp 
                                WHERE pp.paper_id = p.paper_id)";
                    break;
    
                /*-- overdue reviews --*/
                case 4:
    
                    $this->pgtitle ='Papers With Overdue Reviews';
                    $sql .= " WHERE p.decision IS NULL AND 
                                EXISTS(SELECT pr.reviewer_id 
                                FROM paper_reviewers pr 
                                WHERE pr.date_received IS NULL AND 
                                pr.date_due <= CURDATE() AND 
                                pr.paper_id = p.paper_id)";
                    break;
                    
                case 5:
                /*-- ready to publish --*/
                    $this->pgtitle ='Ready to Publish';
                    $this->formInclude='publish_summary';
                    $is_publish = true;
                    $sql .= " WHERE
                              pp.published_paper_id IS NOT NULL
                              AND (pp.is_active !='1' OR pp.is_active IS NULL)";
                    break;
                
                case 6:
                /*-- editors papers --*/
                    $this->pgtitle ='My Papers';
                    $sql .= " WHERE p.editor_user_id = '$_SESSION[user_id]'";
                    break;
                
                case 7:
                /*-- person associations --*/
                    $oPerson = Person::getObject($_POST[people_id]);
                    $this->pgtitle = "$oPerson->first_name $oPerson->last_name's Associated Papers";
                    $sql .= " LEFT OUTER JOIN paper_authors pa ON(p.paper_id=pa.paper_id)
                              LEFT OUTER JOIN paper_reviewers pr ON(p.paper_id=pr.paper_id)
                              WHERE pa.people_id = '$oPerson->people_id' OR
                              pr.people_id = '$oPerson->people_id' OR
                              (p.editor_user_id = u.user_id AND u.people_id = '$oPerson->people_id')";
                    
                    $append_paging = "&people_id=$_POST[people_id]";
                    break;
                
                case 8:
                    /*-- editors papers --*/
                    $this->pgtitle ='Editor Papers';
                    $sql .= " WHERE p.editor_user_id = '$_POST[editor_id]'";
                    break;
                
                default:
    
                    $this->pgtitle ='All Papers';
            }
        
        if($_POST[type] == 1){
            $sql .= " ORDER BY pp.volume DESC, pp.start_page DESC";
        } else {
            $sql .= " ORDER BY p.paper_id desc";
        }
        
        $pager_url = "/paper.php?mode=summary&type=$_POST[type]$append_paging";
        
        $oPager = new Pager(10,$pager_url);
        $this->searchresults = $oPager->getDataSet($sql,'p.paper_id');    
        $this->collectPapers($is_publish); 
        $this->topPaging = $oPager->drawTopPaging(11);
        $this->bottomPaging = $oPager->drawBottomPaging(11);
    }
    
   /*
    *   clean up posted author/person merged data
    */
    private function process_merge(){
        $oAuthor = Author::getObject(NULL,$_POST["people_id"]);

        $oAuthor->paper_id = $_POST['paper_id'];
        $oAuthor->published_first_name = $_POST['published_first_name'];
        $oAuthor->published_middle_name = $_POST['published_middle_name'];
        $oAuthor->published_last_name = $_POST['published_last_name'];
        $oAuthor->submission_author_id = $_POST['submission_author_id'];
        
            /*-- scoop up final author values --*/
            foreach($oAuthor AS $property => $value){
                $select = 'select_'.$property ;
                if($_POST[$select] == "1" || !isset($_POST[$select])){
                    $oAuthor->$property = $_POST[$property];
                }
            }

        return $oAuthor;
    }
}
?>