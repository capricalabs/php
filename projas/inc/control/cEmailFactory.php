<?php 
/*
 *
 *
 * @author		DWN
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: cEmailFactory.php,v 1.5 2006-03-03 18:11:52 dan Exp $
 *
 *
 */

include_once (PROJAS_CONTROL.'/cEmail.php');
include_once (PROJAS_CONTROL.'/cUsers.php');

class cEmailFactory {
    
    /*-- e-mail data --*/
    public $to;
    public $cc;
    public $from;
    public $subject;
    public $body;
    public $paper_id;
    public $user_id;
    public $reviewer_id;
    public $submit_text;
    public $has_attachments;    
    
    /*-- message param --*/
    public $ref;
    
    public function __construct(){
        $this->cc = 'jair-ed@isi.edu';
        $this->from = 'jair-ed@isi.edu';
    }
    
    public function contactAuthor($oPaper) {
        global $myDB;
        
        $this->ref = 'contact_author';
        $oContact = Author::getContact($oPaper->authors);
        $oEmail = cEmail::getObject($this->ref);
            
            $tokens = array(
                 "[paper_id]" => $oPaper->paper_id, 
                 "[contact_name]" => $oContact->last_name
            );
        
        $this->paper_id = $oPaper->paper_id;
        $this->to = $oContact->email1;
        $oEmail = cEmail::replaceTokens($oEmail, $tokens);
        $this->subject = $oEmail->subject;
        $this->body = $oEmail->body;
        $this->submit_text = 'Send';
    }

    public function sendAttachments($oPaper) {
        global $myDB;
        
        $this->ref = 'send_attachments';
        $this->has_attachments = true;
        $oEditor = User::getObject($oPaper->editor_user_id);
        $oEmail = cEmail::getObject($this->ref);
            
            $tokens = array(
                 "[paper_id]" => $oPaper->paper_id, 
                 "[editor_name]" => $oEditor->first_name.' '.$oEditor->last_name
            );
            
        $arReviewer = array();
            foreach($oPaper->reviewers AS $oReviewer){
                $arReviewer[] = $oReviewer->first_name.' '.$oReviewer->last_name.'<'.$oReviewer->email1.'>';
            }
        $this->paper_id = $oPaper->paper_id;
        $this->to = implode(",",$arReviewer);
        $oEmail = cEmail::replaceTokens($oEmail, $tokens);
        $this->subject = $oEmail->subject;
        $this->body = $oEmail->body;
        $this->submit_text = 'Send';
    }
    
    public function remindReviewer($oPaper) {
        global $myDB;
        
        $sAuthor = '';
        $i=0;
        
        $sAuthor = vAdmin::drawAuthors($oPaper->authors,false,false,true);
            
        $this->ref = 'remind_reviewer';
        $oReviewer = Reviewer::getObject($_POST[reviewer_id]);
        $oEditor = User::getObject($oPaper->editor_user_id);
        $oEmail = cEmail::getObject($this->ref);
        
        $tokens = array(
             "[paper_id]" => $oPaper->paper_id, 
             "[title]" => $oPaper->title,
             "[author_list]" => $sAuthor, 
             "[full_name]" => $oReviewer->first_name.' '.$oReviewer->last_name,
             "[editor_name]" => $oEditor->first_name.' '.$oEditor->last_name,
             "[date_due]" => $oReviewer->date_due
        );

        $this->cc .= ($oEditor->email1) ? ', '.$oEditor->email1 : '';
        $this->paper_id = $oPaper->paper_id;
        $this->to = $oReviewer->email1;
        $oEmail = cEmail::replaceTokens($oEmail, $tokens);
        $this->subject = $oEmail->subject;
        $this->body = $oEmail->body;
        $this->submit_text = 'Send Reminder';
        $this->reviewer_id = $_POST[reviewer_id];
    }
    
    public function askEditor($oPaper) {
        global $myDB;
        
        $sAuthor = '';
        $i=0;
        
        $sAuthor = vAdmin::drawAuthors($oPaper->authors,false,false,true);
            
        $this->ref = 'editor_request';
        $oEditor = User::getObject($oPaper->editor_user_id);
        $oEmail = cEmail::getObject($this->ref);
        
        $tokens = array(
            "[paper_id]" => $oPaper->paper_id, 
            "[title]" => $oPaper->title, 
            "[author_list]" => $sAuthor, 
            "[abstract]" => $oPaper->abstract, 
            "[notes]" => $oPaper->notes, 
            "[first_name]" => $oEditor->first_name
        );
        
        $this->has_attachments = true;
        $this->paper_id = $oPaper->paper_id;
        $this->to = $oEditor->email1;
        $oEmail = cEmail::replaceTokens($oEmail, $tokens);
        $this->subject = $oEmail->subject;
        $this->body = $oEmail->body;
        $this->submit_text = 'Send Request';
        $this->user_id = $oPaper->editor_user_id;
    }		
    
    public function publishAlert($oPubPaper){
        $this->ref = 'publish_email';
        $oContact = Author::getContact($oPaper->authors);
        $oEmail = cEmail::getObject($this->ref);
        
        $sAuthor = '';
        $sTo = '';
        $i=0;
        
            if(count($oPubPaper->authors) && is_array($oPubPaper->authors)){
                foreach($oPubPaper->authors AS $author) {
                    $sTo .= ($i>0) ? ', ' : '';
                    $sTo .= $author->first_name.' '. $author->last_name.'<'.$author->email1.'>';
                    $i++;
                }
            }
        
        $oAuthor = Author::getContact($oPubPaper->authors);
        $sContact = ($oAuthor) ? $oAuthor->firt_name : 'Authors';
        $tokens = array(
            "[paper_id]" => $oPubPaper->paper_id, 
            "[paper_info]" => cPublishFactory::drawPaperTitle($oPubPaper,false,false), 
            "[abstract]" => $oPubPaper->abstract_published, 
            "[first_name]" => $oEditor->first_name, 
            "[publish_info]" => "Volume $oPubPaper->volume, pages $oPubPaper->start_page-$oPubPaper->end_page",
            "[url_path]" => 'http://www.jair.org/papers/paper'.$oPubPaper->paper_id.'.html',
            "[contact_name]" => $sContact
        );

        $this->paper_id = $oPubPaper->paper_id;
        $this->to = $sTo;
        $oEmail = cEmail::replaceTokens($oEmail, $tokens);
        $this->subject = $oEmail->subject;
        $this->body = $oEmail->body;
        $this->submit_text = 'Send';
    }
}
?>