<?php 
/*
 *
 *
 *
 * @author		DWN <dogtowngeek@gmail.com>
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: oPaper.php,v 1.1.1.1 2006-02-08 02:24:33 dan Exp $
 *
 *
 */
 
class oPaper {
    
    /* paper info */
    
    public $paper_id;
    public $submission_id;
    public $title;
    public $resub_paper_id;
    public $date_received;
    public $date_acknowledged;
    public $editor_user_id;
    public $editor_name;
    public $date_editor_assigned;
    public $date_editor_accepted;
    public $decision;
	public $decision_date;
    public $abstract;
    public $notes;
    public $address;
    public $is_published;
    
    /*  people collections */
    
    public $authors=array();
    public $reviewers=array();
    
    /*  associated media items */
    
    public $media=array();

    public function __construct($paper_id=NULL) {
        $this->paper_id = $paper_id;
    }
}	

/*-- enables us to automate sql/object automation tasks --*/

class oPaperTable {

	public $strTable;
	public $arrFields;
	
	function oPaperTable() {
		$this->strTable = "papers";
		
		//	array(db field name,		object property		data type
		$this->arrFields[] = array("paper_id",		"paper_id",		"id");
		$this->arrFields[] = array("submission_id",		"submission_id",		"int(11)");
		$this->arrFields[] = array("resub_paper_id",		"resub_paper_id",		"int(11)");
		$this->arrFields[] = array("title",		"title",		"varchar(254)");
		$this->arrFields[] = array("date_received",		"date_received",  "date");
		$this->arrFields[] = array("date_acknowledged",		"date_acknowledged",		"date");
		$this->arrFields[] = array("editor_user_id",		"editor_user_id",		"varchar(254)");
		$this->arrFields[] = array("date_editor_assigned",		"date_editor_assigned",		"date");
		$this->arrFields[] = array("date_editor_accepted",		"date_editor_accepted",		"date");
		$this->arrFields[] = array("decision",		"decision",		"int(1)");
		$this->arrFields[] = array("decision_date",		"decision_date",		"date");
		$this->arrFields[] = array("abstract",		"abstract",		"text");
		$this->arrFields[] = array("notes",		"notes",		"text");
	}
}
?>