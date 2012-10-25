<?php

/*
 *
 *
 *
 * @author		LB, DWN
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: oPerson.php,v 1.1.1.1 2006-02-08 02:24:33 dan Exp $
 *
 *
 */

class oPerson {
    public $people_id;	
    public $first_name;
	public $middle_name;		
    public $last_name;		
    public $email1;		
    public $email2;		
    public $phone;		
    public $address;	
    public $update_date;
    
    function __construct($people_id=NULL) {
        if(!is_null($people_id)){
            $this->people_id = $people_id;
        }
    }
}

/*-- enables us to automate sql/object automation tasks --*/

class oPersonTable {

	public $strTable;
	public $arrFields;

	function oPersonTable()    {
		$this->strTable = "people";
		//	array(db field name,		object property		data type
		$this->arrFields[] = array("people_id",		"people_id",		"id");
		$this->arrFields[] = array("first_name",		"first_name",		"varchar(254)");
		$this->arrFields[] = array("middle_name",		"middle_name",  "varchar(254)");
		$this->arrFields[] = array("last_name",		"last_name",		"varchar(254)");
		$this->arrFields[] = array("email1",		"email1",		"varchar(254)");
		$this->arrFields[] = array("email2",		"email2",		"varchar(254)");
		$this->arrFields[] = array("phone",		"phone",		"varchar(128)");
		$this->arrFields[] = array("address",		"address",		"varchar(68)");
		$this->arrFields[] = array("update_date",		"update_date",		"date");
	}
}
?>