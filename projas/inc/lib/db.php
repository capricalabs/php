<?php
/*
 *
 *
 * @author		LB, DWN
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: db.php,v 1.4 2006-04-01 00:31:01 dan Exp $
 *
 * @revised by:  Dan Netzer
 *
 */
 
class DB {
 
    private $username;
    private $password;
    private $hostname;
    private $database;	
    private $dbo; 
    private $is_remote = false;
    
    function __construct($is_remote=NULL){
        global $myDB;
        
        if(!is_null($is_remote)){
            $this->is_remote = true;
            if($myDB->dbo){
                $myDB->closeConnection();
            }
        }
        
        $this->openConnection();
    }
    
    function setAccountInfo() {
    
        if(!$this->is_remote){
            $this->username = DBUSERNAME;
            $this->password = DBPASSWORD;
            $this->hostname = DBHOST;
            $this->database = DBDATABASE;
        } else {
            $this->is_remote = true;
            $this->username = DBUSERNAME_REMOTE;
            $this->password = DBPASSWORD_REMOTE;
            $this->hostname = DBHOST_REMOTE;
            $this->database = DBDATABASE_REMOTE;
        }
    }
    
    function openConnection() {
        $this->setAccountInfo();
        DEBUG("opened->mysql://$this->username@$this->hostname", "db::OpenConnection", true, SQL);
        $this->dbo = @mysql_connect($this->hostname, $this->username, $this->password);
        
        if($this->dbo === false){
            exit("Unable to connect to mySQL");
        }
        
        mysql_select_db($this->database,$this->dbo);
    }
    
    function closeConnection() {
        global $myDB;
        
        if($this->dbo){
            mysql_close($this->dbo);
            $this->dbo = false;
            $myDB->dbo = false;
            DEBUG("closed->mysql://$this->username@$this->hostname", "db::CloseConnection", true, SQL);
        }
    }
    
    function runQueryInsert($sql) {
        
        if(!$this->dbo){
            $this->openConnection();
        }
        
        DEBUG($sql, "runQueryInsert::SQL", true, SQL);
        
        if (!mysql_query($sql)) {
            ERROR_DEBUG(mysql_error(), "db::query::Issue", true, ERROR);
    		ERROR_DEBUG($sql);
        } else {
            return mysql_insert_id()."";
        }
    }
    
    function runQuerySelect($sql,$pager=false) {
    
        if(!$this->dbo){
            $this->openConnection();
        }
        
		if ($pager) {
			$offset = ($pager->getCurrentPage() - 1) * $pager->getMaxPerPage();
			$sql .= " LIMIT $offset,".$pager->getMaxPerPage();
		} 
        DEBUG($sql, "runQuerySelect::SQL", true, SQL);
        $results = mysql_query($sql)
            or ERROR_DEBUG(mysql_error(), "db::query::Issue", true, ERROR).ERROR_DEBUG($sql);
    
        return $results;
    }
    
    function getRow($sql){
        if(!$this->dbo){
            $this->openConnection();
        }
        
        $result = $this->runQuerySelect($sql);
        return mysql_fetch_assoc($result);
    }
    
    function runQueryUpdate($sql) {
    
        if(!$this->dbo){
            $this->openConnection();
        }
        
        DEBUG($sql, "runQueryUpdate::SQL", true, SQL);
        if (mysql_query($sql) === false) {
            ERROR_DEBUG(mysql_error(), "db::query::Issue", true, ERROR);
            ERROR_DEBUG($sql);
        }
    }
	
	/**
	 * generateInsertSQL - generates the query string
	 * @var		    string
	 * @access	    public
	 * @parameters  $dataObject with new values
	 */
    function generateInsertSQL($o, $sTableSpoof=false) {
		
		$sTableClass = (!$sTableSpoof) ? get_class($o)."Table" : $sTableSpoof."Table";

		$oTable = new $sTableClass;		
		$sql = "INSERT INTO ".$oTable->strTable." (";
		$i = 0;
		
            foreach($oTable->arrFields as $arrField){
                if ($i++ > 0) $sql .= ", ";
                $sql .= $arrField[0];
            }
		
		$sql .= ") VALUES (";
		$i = 0;
		
            foreach($oTable->arrFields as $arrField){
                if ($i++ > 0) $sql .= ", ";
                /*-- for string fields, correct quotes --*/
                if (strstr($arrField[2],"varchar") || strstr($arrField[2],"text")){
                    $sql .= "'".addslashes($this->encodeForHTML($o->$arrField[1]))."'";
                } elseif (strstr($o->$arrField[1], "ADD") || strstr($o->$arrField[1], "NOW()")){
				    $sql .= $o->$arrField[1] ;
                /*-- for integers and dates, if no value, set to null --*/
                } elseif ((strstr($arrField[2],"int") || strstr($arrField[2],"date") || strstr($arrField[2],"timestamp")) && (!strlen($o->$arrField[1]))){
                    $sql .= "NULL";
                /*-- put everything else in quotes --*/
                } else {
                    $sql .= "'".$o->$arrField[1]."'";
                }
            }

		$sql .= ");";

		return $sql;
	}
	
    /**
     * implodeDateValues - integrate posted date data into single value
     *
     *
     *
     */
    function implodeDateValues(&$_POST){
        $newkey = array();
            foreach($_POST as $key=>$value){
             if(!strlen(trim($value))) continue;
                if(strstr($key,"mm_")){
                    $newkey[] = substr($key,3);
                }
                
                if(count($newkey) && is_array($newkey)){
                    foreach($newkey as $date){
                        $year = "yy_".$date;
                        $month = "mm_".$date;
                        $day = "dd_".$date;
                        $_POST[$date] = "$_POST[$year]-$_POST[$month]-$_POST[$day]";
                    }
                }
            }
    }
    
    /**
	 * generateUpdateSQL - generates the query string
	 * @var		    string
	 * @access	    public
	 * @parameters  $dataObject with new values
	 */
	function generateUpdateSQL($o, $sTableSpoof=false) {
		
		$sTableClass = (!$sTableSpoof) ? get_class($o)."Table" : $sTableSpoof."Table";
		$oTable = new $sTableClass;
		
		$sql = "UPDATE ".$oTable->strTable." SET ";
		
            $i = 0;
            foreach($oTable->arrFields as $arrField){
                if ($arrField[2] != "password" && $arrField[2] != "id"){
                    if ($i++ > 0) $sql .= ", ";
                    $sql .= $arrField[0]." = ";
                    /*-- for string fields, correct quotes --*/
                    if (strstr($arrField[2],"varchar") || strstr($arrField[2],"text")){
                        $sql .= "'".addslashes($this->encodeForHTML($o->$arrField[1]))."'";
                    } elseif (strstr($o->$arrField[1], "ADD") || strstr($o->$arrField[1], "NOW()") || strstr($o->$arrField[1], "NULL")){
                        $sql .= $o->$arrField[1] ;
                    /*-- for integers and dates, if no value, set to null --*/
                    } elseif ((strstr($arrField[2],"int") || strstr($arrField[2],"date") || strstr($arrField[2],"timestamp")) && (!strlen($o->$arrField[1]))){
                        $sql .= "NULL";
                    /*-- put everything else in quotes --*/
                    } else {
                        $sql .= "'".$o->$arrField[1]."'";
                    }
                }
            }

		/*-- assuming the first field is always the identifier, set the where clause --*/
		$id = $oTable->arrFields[0][1];
		$sql .= " WHERE ".$oTable->arrFields[0][0]." = '".$o->$id."';";

		return $sql;
	}
	
    /**
	 * assignRowValues - assigns values in row to object based on table info
	 * @var		    string
	 * @access	    public
	 * @parameters  $dataObject with new values
	 */
	static function assignRowValues($o, $row, $sTableSpoof=false) {

		$sTableClass = (!$sTableSpoof) ? get_class($o)."Table" : $sTableSpoof."Table";
		$oTable = new $sTableClass;

            foreach($oTable->arrFields as $arrField){
                $o->$arrField[1] = $row[$arrField[0]];
            }

        return $o;
	}

    /**
	 * assignRowValues - assigns values in row to object based on table info
	 * @var		    string
	 * @access	    public
	 * @parameters  $dataObject with new values
	 */
	static function assignFormValues($o, $sTableSpoof=false) {

		db::implodeDateValues($_POST);

		$sTableClass = (!$sTableSpoof) ? get_class($o)."Table" : $sTableSpoof."Table"; 
		$oTable = new $sTableClass;

            foreach($oTable->arrFields as $arrField){
                if(strlen(trim($_POST[$arrField[1]]))){
                  $o->$arrField[1] = $_POST[$arrField[1]];
                }
            }
		
		return $o;
	}
    
	/**
     * db_fetch_all - returns an array of rows in the result set
     *
     *
     *
     */
	function db_fetch_all($result) {
		$arRows = array();
	    	while ($row = mysql_fetch_array($result)) {
	    		array_push($arRows, $row);
	    	}
	    return ($arRows);
	}
		  	
	function select($sql){
   	    $rs = $this->runQuerySelect($sql);
   	    $arResults = $this->db_fetch_all($rs);
        return $arResults;
	}
	
	/* Returns the input string with all special characters encoded for HTML
	 * "special characters" refers to any character with a decimal value higher than 160
	 *
	 * @parameter $string string
	 * @return string
	 */
	static private function encodeForHTML($string) {
		$return_val = '';
		$length = strlen($string);

            for($i=0;$i<$length;$i++) {
                $return_val .= (ord($string[$i]) > 160) ? "&#".ord($string[$i]).";" : $string[$i];
            }
            
		return $return_val;
	}
 }
?>