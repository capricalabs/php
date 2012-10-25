<?php

/*
 *
 *
 *
 * @author		DWN
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: oSubmission.php,v 1.2 2006-03-19 21:19:39 dan Exp $
 *
 *
 */

class oSubmission {
    
    /*-- public properties --*/
    public  $submission_id;
    public  $resub_paper_id;
    public  $resub_info;
    public  $paper_id;
    public  $abstract;
    public	$date_received;
    public  $notes;
    public  $title;
    public  $acknowledged;
    public  $prelimQ1;
    public  $prelimQ2;
    public  $prelimQ3;
    public  $prelimQ4;
    
   /*-- publish props --*/
    public  $is_publication;
    public  $volume;
    public  $start_page;
    public  $end_page;
    
    /*--for resetting select default--*/
    public  $author_contact;
    public  $author_address;
    
    /*-- paper property --*/
    public  $date_acknowledged;
    
    /*-- author collection --*/
    public  $authors = array();
    
    /*-- media collection --*/
    public  $media = array();
    
    public  function __construct($submission_id) {
        $this->submission_id = $submission_id;
    }
}

class oSubAuthor {
    
    /*-- public properties --*/
    public  $submission_author_id;
    public  $submission_id;
    public  $first_name;
    public  $last_name;
    public  $middle_name;
    public  $email;
    public  $is_contact;
    public  $address;
    public  $acknowledged='0';
    public  $author_count;
    
}

/*-- enables us to automate sql/object automation tasks --*/

class oSubmissionTable {

	var $strTable;
	var $arrFields;
	
	function oSubmissionTable()    {
		$this->strTable = "submissions";
		//	array(db field name,		object property		data type
		$this->arrFields[] = array("submission_id",		"submission_id",		"id");
		$this->arrFields[] = array("resub_paper_id",		"resub_paper_id",		"int(11)");
		$this->arrFields[] = array("paper_id",		"paper_id",		"int(11)");
		$this->arrFields[] = array("abstract",		"abstract",  "text");
		$this->arrFields[] = array("date_received",		"date_received",		"date");
		$this->arrFields[] = array("notes",		"notes",		"varchar(254)");
		$this->arrFields[] = array("title",		"title",		"text");
		$this->arrFields[] = array("acknowledged",		"acknowledged",		"varchar(1)");
		$this->arrFields[] = array("prelimq1",		"prelimQ1",		"text");
		$this->arrFields[] = array("prelimq2",		"prelimQ2",		"text");
		$this->arrFields[] = array("prelimq3",		"prelimQ3",		"text");
		$this->arrFields[] = array("prelimq4",		"prelimQ4",		"text");
		//pub data
		$this->arrFields[] = array("volume",		"volume",		"int(11)");
		$this->arrFields[] = array("start_page",		"start_page",		"int(11)");
		$this->arrFields[] = array("end_page",		"end_page",		"int(11)");
		$this->arrFields[] = array("is_publication",		"is_publication",		"int(1)");
	}
}

class oSubAuthorTable {

	var $strTable;
	var $arrFields;
	
	function oSubAuthorTable()    {
		$this->strTable = "submission_authors";
		//	array(db field name,		object property		data type
		$this->arrFields[] = array("submission_author_id",		"submission_author_id",		"id");
		$this->arrFields[] = array("submission_id",		"submission_id",		"int(11)");
		$this->arrFields[] = array("first_name",		"first_name",		"varchar(254)");
		$this->arrFields[] = array("middle_name",		"middle_name",  "varchar(254)");
		$this->arrFields[] = array("last_name",		"last_name",		"varchar(254)");
		$this->arrFields[] = array("email",		"email",		"varchar(254)");
		$this->arrFields[] = array("is_contact",		"is_contact",		"int(1)");
		$this->arrFields[] = array("address",		"address",		"varchar(68)");
		$this->arrFields[] = array("acknowledged",		"acknowledged",		"varchar(1)");
	}
}
?>