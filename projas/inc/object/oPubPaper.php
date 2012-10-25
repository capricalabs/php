<?php 
/*
 *
 *
 *
 * @author		DWN <dogtowngeek@gmail.com>
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: oPubPaper.php,v 1.1.1.1 2006-02-08 02:24:33 dan Exp $
 *
 *
 */
 
include_once (PROJAS_OBJECT.'/oPaper.php');

class oPubPaper extends oPaper {
    
    public $published_paper_id;
    public $published_submission_id;
    public $title_published;
    public $abstract_published;
    public $volume;
    public $start_page;
    public $end_page;
    public $date_acknowledged;
    public $publish_date;
    public $is_active;
    
    public function __construct($published_paper_id=NULL) {
        $this->published_paper_id = $published_paper_id;
    }

}	

/*-- enables us to automate sql/object automation tasks --*/

class oPubPaperTable {

	public $strTable;
	public $arrFields;
	
	function oPubPaperTable() {
		$this->strTable = "published_papers";
		
		//	array(db field name,		object property		data type
            $this->arrFields = array(
                array("published_paper_id",		"published_paper_id",		"id"),
                array("paper_id",		        "paper_id",		"int(11)"),
                array("submission_id",		        "published_submission_id",		"int(11)"),
                array("volume",		"volume",		"int(11)"),
                array("start_page",		"start_page",		"int(11)"),
                array("end_page",		"end_page",		"int(11)"),
                array("date_acknowledged",		"date_acknowledged",		"date"),
                array("publish_date",		"publish_date",		"date"),
                array("title",		"title_published",		"varchar(128)"),
                array("abstract",		"abstract_published",		"text"),
                array("is_active",		"is_active",		"tinyint(1)")
            );
	}
}
?>