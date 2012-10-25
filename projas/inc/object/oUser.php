<?php
/*
 *
 *
 *
 * @author		LB, DWN
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: oUser.php,v 1.1.1.1 2006-02-08 02:24:33 dan Exp $
 *
 *
 */

include_once (PROJAS_OBJECT.'/oPerson.php');

class oUser extends oPerson {

    public $user_id;
    public $user_type_id;
    public $user_type;
    public $username;
    public $password;
    public $active;
    public $login_date;

    function __construct($user_id=NULL) {
        if(!is_null($user_id)){
            $this->user_id = $user_id;
        }
    }
}

class oUserTable {

	public $strTable;
	public $arrFields;
	
	function oUserTable()    {
		$this->strTable = "users";
		//	array(db field name,		object property		data type
		$this->arrFields[] = array("user_id",		"user_id",		"id");
		$this->arrFields[] = array("people_id",		"people_id",		"int(11)");
		$this->arrFields[] = array("user_type_id",	"user_type_id",		"int(11)");
		$this->arrFields[] = array("username",		"username",		"varchar(128)");
		$this->arrFields[] = array("password",		"password",		"varchar(128)");
		$this->arrFields[] = array("active",		"active",  "int(1)");
		$this->arrFields[] = array("login_date",	"login_date",  "date");
	}
}
?>