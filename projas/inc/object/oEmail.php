<?php 
/*
 *
 *
 *
 * @author		DWN
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: oEmail.php,v 1.1.1.1 2006-02-08 02:24:33 dan Exp $
 *
 *
 */
 
class oEmail {

   /*
    *   public properties
    */
    public $email_id;
    public $name;
    public $ref;
    public $default_subject;
    public $text_body;
}	

/*-- enables us to automate sql/object automation tasks --*/

class oEmailTable {

	public $strTable;
	public $arrFields;
	
	function oEmailTable() {
		$this->strTable = "emails";
		
		//	array(db field name,		object property		data type
		$this->arrFields[] = array("email_id",	"email_id",		"id");
		$this->arrFields[] = array("name",		"name",		    "varchar(64)");
		$this->arrFields[] = array("ref",		"reference",		"varchar(20)");
		$this->arrFields[] = array("subject",   "subject",		"varchar(255)");
		$this->arrFields[] = array("body",		"body",     "text");
	}
}
?>