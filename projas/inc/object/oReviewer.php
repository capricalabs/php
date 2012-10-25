<?php
/*
 *
 *
 *
 * @author		Dan Netzer
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: oReviewer.php,v 1.1.1.1 2006-02-08 02:24:33 dan Exp $
 *
 *
 */
 
include_once (PROJAS_OBJECT.'/oPerson.php');

class oReviewer extends oPerson {

    public $reviewer_id;
    public $paper_id;
    public $date_due;
    public $date_received;
    public $total_reminders_sent = 0;
    public $date_last_reminder;
    public $editor_notes;
        
    public function __construct($reviewer_id=NULL) {
        $this->reviewer_id = $reviewer_id;
    }
}		

class oReviewerTable {

	public $strTable;
	public $arrFields;
	
	function oReviewerTable()    {
		$this->strTable = "paper_reviewers";
		//	array(db field name,		object property		data type
		$this->arrFields[] = array("reviewer_id",		"reviewer_id",		"id");
		$this->arrFields[] = array("people_id",		"people_id",		"int(11)");
		$this->arrFields[] = array("paper_id",		"paper_id",		"int(11)");
		$this->arrFields[] = array("date_due",		"date_due",  "date");
		$this->arrFields[] = array("date_received",		"date_received",		"date");
		$this->arrFields[] = array("total_reminders_sent",		"total_reminders_sent",		"int(11)");
		$this->arrFields[] = array("date_last_reminder",		"date_last_reminder",		"date");
		$this->arrFields[] = array("editor_notes",		"editor_notes",		"text");
	}
}
?>