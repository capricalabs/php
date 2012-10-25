<?
/*
 *
 *
 *
 * @author		DWN<dogotowngeek@gmail.com>
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: util.php,v 1.2 2006-04-23 14:57:32 dan Exp $
 *
 */


function msTimeStampToPHP($timeStamp=NULL){

        if(is_null($timeStamp)){
            return false;
        }
        
    $arTime = explode("-", $timeStamp);
    $hours = 0;
    $minutes = 0;
    $seconds = 0;
    $month = $arTime[1];
    $day = $arTime[2];
    $year = $arTime[0];
    
    return mktime($hours, $minutes, $seconds, $month, $day, $year);
}

function debug_print_object($obj, $color = '#000000', $bExit = false) {

    print "<pre style=\"color: $color\">";
    print_r($obj);
    print "</pre>";
    if ($bExit) exit;
}

function OneDimensionalize($ar, $sField, $keepNulls = true) {
	if(!is_array($ar)) {
		return $ar;
	} else {
		
		$retVal = array();
		
		foreach($ar as $item) {
			if(array_key_exists($sField, $item) || $keepNulls) {
				$retVal[] = $item[$sField];
			}
		}

		return $retVal;
	}
}

function GOTO ($url) {
	$SERVER_NAME = $_SERVER["HTTP_HOST"];
	$SERVER_PORT = getenv("SERVER_PORT");

	if ($SERVER_PORT == 80 || $SERVER_PORT == 81) {
		$SERVER_PORT = '';
	}
	$SCRIPT_NAME = getenv("SCRIPT_NAME");

	if (! eregi("^http", $url)) {
		if (! eregi("^/", $url)) {
			$currpath = ereg_replace("/[^/]*$", "/", getenv("SCRIPT_NAME"));
		} else {
			$currpath = '';
		}
		if ($SERVER_PORT == "443") {
			$url = "https://" . $SERVER_NAME . $currpath . $url;
		} else {
			$url = "http://" . $SERVER_NAME . ($SERVER_PORT ? ":" . $SERVER_PORT : "") . $currpath . $url;
		}
	}

	Header("Location: " . $url);
	exit;
}

function ERROR_DEBUG($sDebug, $sKey = false, $bEnsureUnique = false, $iLevel = ERROR) {
	
	$sKey = "<SPAN class=\"error\">" . $sKey . "</SPAN>";
	DEBUG($sDebug, $sKey, $bEnsureUnique, $iLevel);
	
}

function DEBUG($sDebug, $sKey = false, $bEnsureUnique = true, $iLevel = INFO) {
    global $bDebug;
    
    if($bDebug){
        switch($iLevel) {
            case ERROR:
                $style="errorText";
                $admonition = "<SPAN class=\"error\">ERROR: </span>";
                break;
            case WARNING:
                $style="warningText";
                $admonition = "WARNING: ";
                break;
            default:
                $style = null;
                $admonition = null;
        }
        
        //Retrieve any existing debug strings
        $arExisting = $_SESSION['debugItems'];	
        
        //If $bEnsure unique is true, append a string to the key
        //which ensures its uniqueness, but will not display on DEBUG_PRINT
        if($bEnsureUnique) {
            $sKey .= "<SPAN style=\"display: none\">" . mt_rand() . "</SPAN>";
        }
        
        //handle any necessary formatting
        if($style && $admonition && $sKey) {
            $sKey = "<span class=\"$style\">$admonition</span>" . $sKey;
        }	
        
        //Get the size of the existing session elements, 
        //if there are none, create an empty array.	
        $iLenExisting = sizeof($arExisting);		
        if($iLenExisting == 0) {
            $arExisting = array();
        }
        
        //If there's no key, then just chuck it in, 
        //otherwise, append to this $sKey value.
        if($sKey === false) {
            //Append the new item to the array
            array_push($arExisting, $sDebug);
        } else {
            $arExisting[$sKey] = $sDebug;
        }
       
        //Add into the session	
        $_SESSION['debugItems'] = $arExisting;
    }
}

function DEBUG_PRINT() {
    global $bDebug;
    
    if($bDebug){
		//Retrieve the debug items from the session and print
		$arDebug = $_SESSION['debugItems'];	
		if ((gettype($arDebug) == "array") && (sizeof($arDebug) > 0)) {	
			print ListArray($arDebug);
		}

	   //After printing, clear the session item
	   unset($_SESSION['debugItems']);	
	}
}


function ListArray($var, $title='ListArray') { 
	if($title)	{ $string = ''; }

	$string = '<br><br><table border="0" cellpadding="5" cellspacing="1" bgcolor="CCCCCC">';
	if($title){ $string .= '<tr bgcolor="003346" style="color: whitesmoke;"><td><b>Key</b></td><td><b>Value</b></td></tr>'; }

    if (is_array($var)) {  // the while is breaking (ITF)
    	while(list($key, $val) = each($var)) {
    		$string .= '<tr bgcolor="whitesmoke">' ;
    		$string .= "<td valign=\"top\" nowrap><b>$key</b></td><td valign=\"top\">";
    		if(is_array($val)){
    			$string .= ListArray($val, '');
    		}else{ 
    			$string .= "$val" ; 
    		}
    
    		$string .= '</td></tr>';
    	}//While
    }
	
	$string .= "</table><br><br>";

	if($title){ $string .= ''; }
	return $string;
}

function br2nl($text){
   /* Remove XHTML linebreak tags. */
   $text = str_replace("<br />","",$text);
   /* Remove HTML 4.01 linebreak tags. */
   $text = str_replace("<br>","",$text);
   /* Remove retarded linebreak tags. */
   $text = str_replace("</br>","",$text);
   /* Return the result. */
   return $text;
}
?>