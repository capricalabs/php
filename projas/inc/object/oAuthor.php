<?php

/*
 *
 *
 *
 * @author		DWN, LB
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: oAuthor.php,v 1.1.1.1 2006-02-08 02:24:33 dan Exp $
 *
 *
 */
 
include_once (PROJAS_OBJECT.'/oPerson.php');

class oAuthor extends oPerson {

    public $author_id;
    public $submission_author_id;
    public $paper_id;
    public $is_contact = false;
    public $published_first_name;
	public $published_last_name;
	public $published_middle_name;
	public $is_published;

    public function __construct($author_id=NULL) {
        $this->author_id = $author_id;
    }
}

/*-- enables us to automate sql/object automation tasks --*/

class oAuthorTable {

	public $strTable;
	public $arrFields;
	
	function oAuthorTable()    {
		$this->strTable = "paper_authors";
		//	array(db field name,		object property		data type
		$this->arrFields[] = array("author_id",		"author_id",		"id");
		$this->arrFields[] = array("people_id",		"people_id",		"int(11)");
		$this->arrFields[] = array("paper_id",		"paper_id",  "int(11)");
		$this->arrFields[] = array("is_contact",		"is_contact",		"int(1)");
		$this->arrFields[] = array("published_first_name",		"published_first_name",		"varchar(254)");
		$this->arrFields[] = array("published_middle_name",		"published_middle_name",		"varchar(254)");
		$this->arrFields[] = array("published_last_name",		"published_last_name",		"varchar(254)");
		$this->arrFields[] = array("is_published",		"is_published",		"int(1)");
	}
}
?>