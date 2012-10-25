<?php
/*
 *
 *
 *
 * @author		DWN <dogtowngeek@gmail.com>
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: vAdmin.php,v 1.14 2006-03-25 04:18:15 dan Exp $
 *
 *
 *
 */
 
 class vAdmin {
 
    function displayFilters(){
?>
    <table cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td>
<?
        if($_POST[mode] == "submit"){
            $filters = "<strong>Filters:</strong><br>";
            $filters .=($_POST[people_id])?"Author: $_POST[name]<br>":'';
            $filters .=($_POST[year_begin])?"Published: $_POST[month_begin]-$_POST[year_begin] through $_POST[month_end]-$_POST[year_end]<br>":'';
            $filters .=($_POST[rev_years])?"Review Time: $_POST[review_time] $_POST[rev_years] Years $_POST[rev_months] months<br>":'';
            $filters .=($_POST[filter])?"Status: Is $_POST[filter]<br>":'';
            
            echo $filters;
        }
?>
            </td>
        </tr>
    </table>
<?
    }
    
    function drawSubNav($arSubNav, $sSection){
    
        if(is_array($arSubNav) && count($arSubNav)){
            $sNav = "<span class=\"subnavtitle\">$sSection</span><br><img src=\"/images/spacer.gif\" width=\"50\" height=\"4\"><br>";
            foreach($arSubNav as $name => $link){
                $style = ($name == "Process New" || $name == "My Papers") ? 'process' : 'subnav';
                $sNav .= "&nbsp;&nbsp;<span class=\"$style\"><a href=\"$link\">$name</a></span><br>";
            }
            return $sNav.'<br><br><br>';
        }
    }

    static function formatDate($date, $include_time=false, $include_day=false, $super_short=false){
        
            if($date == "0000-00-00" || is_null($date)){
                return false;
            }
            
            if(!$super_short){
                $format = ($include_day) ? 'M d, Y' : 'M, Y';
                $format = ($include_time) ? $format.' - g:ia' : $format;
            } else {
                $format = 'n/j/y';
            }
        
        return date($format, strtotime($date));
    }
    
    static function drawYear($date){
        return date('Y',strtotime($date));
    }
    
    
    static function padString($string, $length){
        if(strlen($string) > $length){
            $string = substr($string, 0, $length-3);
            $string = $string.'...';
        }
        
        return $string;
    }
    
    static function drawAddress($oAuthors){
        if(count($oAuthors)){
           foreach($oAuthors AS $author){
               if($author->is_contact){
                   return (strlen(trim($author->address))) ? $author->address : 'Contact author has no address';
               }
           }
           return 'No Contact Author is flagged';
        }
        return 'There are no authors';
    }
    
    static function drawAuthors($oAuthors, $is_submission=false,$is_published=false,$is_email=false){
       if(count($oAuthors)){
           $arAuthor = array();
               foreach($oAuthors AS $oAuthor){
                    if($is_published){
                        $author = $oAuthor->published_first_name.' ';
                        $author .= (strlen($oAuthor->published_middle_name)) ? ' '.$oAuthor->published_middle_name : '';
                        $author .= ' '.$oAuthor->published_last_name;
                    } else {
                        $author = $oAuthor->first_name.' '.$oAuthor->last_name;
                    }
                    
                    $email = ($is_submission) ? $oAuthor->email : $oAuthor->email1 ;
                        /*-- first author is contact, make bold and include e-mail --*/
                        if($oAuthor->is_contact && !$is_published){
                            if($is_email){
                                $author = "$author ($email)";
                            } else {
                                $author = "<span class=\"bold\">$author</span> (<a href=\"mailto:$email\" class=\"e-mail\">$email</a>)";
                            }
                        }
                    
                    $arAuthor[] = $author;
               }
               
           $last_author = array_slice($arAuthor,-1,1);
           $arAuthor = array_slice($arAuthor,0,-1);
           $author_list = implode(", ",$arAuthor);
           $author_list = (strlen($author_list)) ? $author_list.' and ': '';
           return $author_list.$last_author[0];
       } else {
           return 'No Authors Listed';
       }
    }
    
    static function drawReviewers($oReviewers){
        if(count($oReviewers)){
           $arReviewer = array();
               foreach($oReviewers AS $oReviewer){
                    $date_due = msTimeStampToPHP($oReviewer->date_due) ;
                    $current_timestamp = mktime();

                    if($oReviewer->date_received){
                        $arReviewer[] = '<span class="success">'.$oReviewer->first_name.' '.$oReviewer->last_name.'</span>';
                    } elseif($current_timestamp > $date_due) {
                        $reviewer = '<span class="error">'.$oReviewer->first_name.' '.$oReviewer->last_name;
                        $date = ($oReviewer->date_due)?vAdmin::formatDate($oReviewer->date_due,false,false,true):'No Due Date';
                        $reviewer .= ' ('.$date.')</span>';
                        $arReviewer[] = $reviewer;
                    } else {
                        $arReviewer[] = $oReviewer->first_name.' '.$oReviewer->last_name ;
                    }
               }
           return implode(", ",$arReviewer);
       } else {
           return 'No Reviewers Assigned';
       }
    }

    static function drawEditor($oPaper){
       if($oPaper->editor_user_id){
           $oUser = User::getObject($oPaper->editor_user_id);
           $sEditor = $oUser->first_name.' '.$oUser->last_name.' (<a href="mailto:'.$oUser->email1.'" class="e-mail">'.$oUser->email1.'</a>)'." - <a href=\"/paper.php?mode=assign_editor&paper_id=$oPaper->paper_id\" class=\"edit\">Assign New</a>";
           $sEditor .= ($oPaper->date_editor_assigned && !$oPaper->date_editor_accepted) ? '<br>Requested: '.self::formatDate($oPaper->date_editor_assigned, true, true) : '';
           $sEditor .= (!$oPaper->date_editor_accepted) ? " - <a href=\"/paper.php?mode=confirm_editor&paper_id=$oPaper->paper_id\" class=\"edit\">Confirm</a>" : '';
           $sEditor .= ($oPaper->date_editor_accepted) ? '<br><span class="success">Confirmed:</span> '.self::formatDate($oPaper->date_editor_accepted, true, true) : '';
           $sEditor .= ($oPaper->date_editor_accepted) ? " - <a href=\"/paper.php?mode=unconfirm_editor&paper_id=$oPaper->paper_id\" class=\"edit\">UnConfirm</a>" : '';
           return $sEditor;
       } else {
           return "<a href=\"/paper.php?mode=assign_editor&paper_id=$oPaper->paper_id\" class=\"edit\">Assign Editor</a>";
       }
    }	
    
    static public function getDecisionOptions($oPaper,$is_search=NULL) {
        global $arPaperDecisions, $arSearchDecisions;

        $arDecisions = (!is_null($is_search)) ? $arSearchDecisions : $arPaperDecisions;
        $options = '';
            foreach($arDecisions AS $key=>$decision) {
                $selected = ($oPaper->decision == "$key") ? ' selected' : '';
                $options .= "<option value=\"$key\"$selected>$decision</option>\n";
            }
        
        return $options;
    }
    
    static public function getEmails() {
        
        $arEmails = cEmail::getObjects();
        $options = '';
            foreach($arEmails AS $oEmail) {
                $options .= "<option value=\"$oEmail->email_id\"$selected>$oEmail->name</option>\n";
            }
        
        return $options;
    }
    
    static public function drawPublishInfo($oPaper){
        if($oPaper->published_paper_id){
            $publish = "Volume $oPaper->volume, Pages $oPaper->start_page - $oPaper->end_page";
            return $publish;
        } else {
            return 'Unpublished';
        }
    }

    static public function getUserTypes($oUser) {
        global $myDB;
        
        $sql = "SELECT user_type_id, user_type FROM user_types ORDER BY user_type";
        $results = $myDB->select($sql);
        $options = '';
            foreach($results AS $row) {
                $selected = ($row["user_type_id"] == $oUser->user_type_id) ? ' selected' : '';
                $options .= "\r\n<option value=\"$row[user_type_id]\"$selected>$row[user_type]</option>";
            }

        return $options;
    }

    static public function getMediaTypes($oMedia, $is_submission=false, $is_published=false) {
        global $myDB;
        
        $results = Media::getMediaTypes($is_published);
        $options = '';
        
            if(!$is_submission){
                foreach($results AS $row) {
                    $selected = ($row["media_type_id"] == $oMedia->media_type_id) ? ' selected' : '';
                    $options .= "\r\n<option value=\"$row[media_type_id]\"$selected>$row[media_type]</option>";
                }
            } else {
                $options .= "\r\n<tr><td valign=\"top\"><input type=\"radio\" name=\"media_type_id\" value=\"1\"></td><td><span class=\"bold\">Paper</span><br>PostScript File</td></tr>";
                foreach($results AS $row) {
                    $selected = ($row["media_type_id"] == $oMedia->media_type_id) ? ' checked' : '';
                    $options .= "\r\n<tr><td valign=\"top\"><input type=\"radio\" name=\"media_type_id\" value=\"$row[media_type_id]\"$selected></td><td><span class=\"bold\">$row[media_type]</span><br>$row[description]</td></tr>";
                }
            }
            
        return $options;
    }
    
    function parseDate($value,&$month,&$day,&$year){
        $arDate = explode("-",$value);
        $year = (int)$arDate[0];
        $month = (int)$arDate[1];
        $day = (int)$arDate[2];
    }

    function getDateSelect($name,$value){
        self::parseDate($value,$month,$day,$year);
?>    
      <select name="mm_<?=$name?>">
        <option value="">month</option>
<?
        for ($i=1;$i<=12;$i++){
            $sSelected = ($month==$i)?' selected':'';
            echo "<option value=\"$i\"$sSelected>$i</option>\n";
        }
?>
      </select>&nbsp;
      <select name="dd_<?=$name?>">
        <option value="">day</option>
<?
        for ($j=1;$j<=31;$j++){
            $sSelected = ($day==$j)?' selected':'';
            echo "<option value=\"$j\"$sSelected>$j</option>\n";
        }
?>
      </select>&nbsp;
      <select name="yy_<?=$name?>">
        <option value="">year</option>
<?
        for ($k=1993;$k<=2015;$k++){
            $sSelected = ($year==$k)?' selected':'';
            echo "<option value=\"$k\"$sSelected>$k</option>\n";
        }
?>
      </select>
<?  
    }
    
    static public function drawError($sErrorMessage){
        if($sErrorMessage){
            $message = '<fieldset>
                        <legend><span class="error-message">Error:</span></legend>'.
                        '<span class="error-message">'.$sErrorMessage.'</span><br>'
                        .'</fieldset><br>';
        } else {
            $message = '';
        }
        
        return $message;
    }    
    
    static public function drawSuccess($sSuccessMessage){
        if($sSuccessMessage){
            $message = '<fieldset>
                            <legend><span class="success-message">Success:</span></legend>'.
                            '<span class="success-message">'.$sSuccessMessage.'</span>'
                        .'</fieldset><br>';
        } else {
            $message = '';
        }
        return $message;
    }
    
    static public function drawPlainMessage($sMessage){
        if($sMessage){
            $message = '<fieldset><br>'
                        .'<span class="bold">'.$sMessage.'</span><br><br>'
                    .'</fieldset><br>';
        } else {
            $message = '';
        }
        return $message;
    }
    
    static function drawMessage($sErrorMessage=NULL, $sSuccessMessage=NULL, $sMessage=NULL){
        if(!is_null($sErrorMessage)){
            return self::drawError($sErrorMessage);
        } elseif(!is_null($sSuccessMessage)){
            return self::drawSuccess($sSuccessMessage);
        } elseif(!is_null($sMessage)){
            return self::drawPlainMessage($sMessage);
        }
    }
    
    static function getFileInfo($oMedia){
        
        $sFile = '<table cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td>'.Media::getIcon($oMedia).'&nbsp;</td>
                        <td>'.Media::getFileLink($oMedia).'</td>
                    </tr>
                </table>';

        return $sFile;
    }
    
    static function getActiveUsers(){
        $arUsers = User::getActiveUsers();
        
        if(count($arUsers) && is_array($arUsers)){
            $sUsers = '<table cellpadding="2" cellspacing="0" border="0">';
                foreach($arUsers AS $user){
                    $oUser = User::getObject($user[user_id]);
                        if($not_first){
                            //$sUsers .= ', ';
                        }
                       
                    $sUsers .= '<tr><td><a href="#" class="edit">'.$oUser->first_name.' '.$oUser->last_name.'</a></td>';
                    $sUsers .= '<td>&nbsp;&nbsp;<span class="">'.self::formatDate($oUser->login_date,true,true).'</span></td></tr>';
                    $not_first = true;
                }
            $sUsers .= '</table>';
            return $sUsers;
        } else {
            return 'None';
        }
    }
    
    static public function drawFiles($arMedia, $is_public=NULL){
        if(is_array($arMedia) && count($arMedia)){
            $sMedia = '<table cellpadding="0" cellspacing="0" border="0"><tr>';
            $i=0;
                foreach($arMedia AS $oMedia){
                    if(!is_null($is_public) && $oMedia->is_public != '1') continue;
                    $i++;

                        if($not_first){
                            $sMedia .= '<td valign="bottom">,&nbsp;</td>';
                        }

                        /*-- close, and open new table if we've drawn 3 files --*/
                        if($i==4 || $i==7 || $i ==10 || $i ==13){
                            $sMedia .= '</tr></table>';
                            $sMedia .= '<table cellpadding="0" cellspacing="0" border="0"><tr>';
                        }
                        
                    $sMedia .= '<td>'.self::getFileInfo($oMedia).'</td>';
                    $not_first = true;
                }
            $sMedia .= '</tr></table>';
            return ($i==0)?'None':$sMedia;
        } else {
            return 'None';
        }
    }
    
    static public function drawBreadCrumb($oPaper){
        $sObject = get_class($oPaper);
        
        if($sObject == "oPubPaper"){
            $sSubmit = ($oPaper->submission_id) ? '<a href="/paper.php?mode=submission_details&submission_id='.$oPaper->submission_id.'" class="crumb">Submission</a>' : '<span class="inactive">Submission</span>';
            $sPaper = '<a href="/paper.php?mode=paper_details&paper_id='.$oPaper->paper_id.'" class="crumb">Review</a>';
            $sPublish = '<span class="bold">Publish</span>';
        } else {
            $oPubPaper = cPublish::getObject($oPaper->paper_id);
        }

        if ($sObject == "oPaper"){
            $sSubmit = ($oPaper->submission_id) ? '<a href="/paper.php?mode=submission_details&submission_id='.$oPaper->submission_id.'" class="crumb">Submission</a>' : '<span class="inactive">Submission</span>';
            $sPaper = '<span class="bold">Review</span>';
            $sPublish = (is_object($oPubPaper)) ? '<a href="/publish.php?mode=publish_details&paper_id='.$oPubPaper->paper_id.'" class="crumb">Publish</a>' : '<span class="inactive">Publish</span>';
        }

        if ($sObject == "oSubmission"){
            $sSubmit = '<span class="bold">Submission</span>';
            $sPaper = '<a href="/paper.php?mode=paper_details&paper_id='.$oPaper->paper_id.'" class="crumb">Review</a>';
            $sPublish = (is_object($oPubPaper)) ? '<a href="/publish.php?mode=publish_details&paper_id='.$oPubPaper->paper_id.'" class="crumb">Publish</a>' : '<span class="inactive">Publish</span>';
        }

        return '<div align="left">'.$sSubmit.'&nbsp;&raquo;&nbsp;'.$sPaper.'&nbsp;&raquo;&nbsp;'.$sPublish.'<br><br></div>';
    }
    
    static public function getPublishedVolumeOptions(){
        $i = cPublishFactory::getMaxVolume();
        $sSelect="\n<option value=\"\">All Volumes</option>";
            while($i > 0){
                $sSelect.="\n<option value=\"$i\">Volume $i</option>";
                $i--;
            }
        return $sSelect;
    }
}
?>