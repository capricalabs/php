<?php 
/*
 *
 *
 *
 * @author		DWN
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: oMedia.php,v 1.1.1.1 2006-02-08 02:24:33 dan Exp $
 *
 *
 */
 
class oMedia {

   /*
    *   public properties
    */
    public $media_item_id;
    public $submission_id;
    public $paper_id;
    public $name;
    public $file_type_id;
    public $media_type_id;
    public $notes;
    public $file_size;
    public $file_ext;
    public $document_url;
    public $date_added;
    public $date_updated;
    public $is_public;
    public $is_published;
    
    public $transfer_error;
    public $sub_dir;
    public $file_upload_pointer;
	
}	

/*-- enables us to automate sql/object automation tasks --*/

class oMediaTable {

	public $strTable;
	public $arrFields;
	
	function oMediaTable() {
		$this->strTable = "media_items";
		
		//	array(db field name,		object property		data type
		$this->arrFields[] = array("media_item_id",		"media_item_id",		"id");
		$this->arrFields[] = array("name",		"name",		"varchar(64)");
		$this->arrFields[] = array("submission_id",		"submission_id",		"int(11)");
		$this->arrFields[] = array("paper_id",		"paper_id",		"int(11)");
		$this->arrFields[] = array("file_type_id",		"file_type_id",  "int(11)");
		$this->arrFields[] = array("media_type_id",		"media_type_id",  "int(11)");
		$this->arrFields[] = array("notes",		"notes",		"text");
		$this->arrFields[] = array("file_size",		"file_size",		"int(11)");
		$this->arrFields[] = array("file_ext",		"file_ext",		"varchar(16)");
		$this->arrFields[] = array("document_url",		"document_url",		"varchar(256)");
		$this->arrFields[] = array("is_published",		"is_published",		"int(1)");
		$this->arrFields[] = array("is_public",		"is_public",		"int(1)");
		$this->arrFields[] = array("date_added",		"date_added",		"date");
		$this->arrFields[] = array("date_updated",		"date_updated",		"datetime");
	}
}
?>