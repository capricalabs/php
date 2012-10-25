<?php
/*
 *
 *
 *
 * @author		LB
 * @copyright	(c) 2005 PROJAS
 * @version     $Id: livesearch.php,v 1.2 2006-02-11 02:21:51 dan Exp $
 *
 */

function liveSearch($mode, $name){
    global $myDB;
    
    if($mode == "author"){
        $sql = "SELECT DISTINCT p.people_id, CONCAT(first_name,' ',last_name) AS full_name 
                FROM people p
                INNER JOIN paper_authors pa ON(p.people_id=pa.people_id)
                WHERE LOWER(CONCAT(first_name,' ',last_name)) LIKE '%".strtolower($name)."%' 
                ORDER BY first_name, last_name LIMIT 8";
    } else {
        $sql = "SELECT DISTINCT p.people_id, CONCAT(first_name,' ',last_name) AS full_name 
                FROM people p
                INNER JOIN users u ON(p.people_id=u.people_id)
                INNER JOIN papers pa ON(pa.editor_user_id=u.user_id)
                WHERE LOWER(CONCAT(first_name,' ',last_name)) LIKE '%".strtolower($name)."%' 
                ORDER BY first_name, last_name LIMIT 8";
	}

	$results = $myDB->select($sql);
	$html = '<span class="error">No Results</span>';
	$is_start = true;
	
        if(count($results) && is_array($results)){
            foreach($results AS $row){
                    if($is_start){
                        $html = "<?xml version=\"1.0\" encoding=\"utf-8\"  ?>";
                        $html .= '<span class="bold">Select '.ucfirst($mode).':</span><br>';
                    }
                    if(strlen(trim($row[full_name]))){
                        $output = "<span onclick=\"javascript:fillName('$row[full_name]',' $row[people_id]');\">$row[full_name]\n</span>";
                        $html .= "<a href=\"#\">$output</a><br>";
                    }
                $is_start = false;
            }
            $html .= '<div align="right"><a href="#" onClick="liveSearchHide();return false;" class="edit">Close</a>&nbsp;</div>';
        }
        
    return $html;
}
?>