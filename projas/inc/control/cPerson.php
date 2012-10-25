<?php
/*
 *
 *
 *
 * @author		DWN<dogtowngeek@gmail.com>
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: cPerson.php,v 1.17 2006-03-28 01:14:12 dan Exp $
 *
 *
 */

include_once (PROJAS_OBJECT.'/oPerson.php');

class Person extends cMasterController{
    
    public function renderPage(){
    
        switch($this->action){
            
            case "edit":
                $this->formInclude='edit_person';
                $oPerson = self::getObject($_POST[people_id]);
                $this->oTemp = $oPerson;
                break;
            
            case "update":
                $this->success_message = 'Person has been updated in the system';
                $oPerson = self::getFormObject($_POST[people_id]);
                self::saveObject($oPerson);
		        $this->action = "list";
		        $this->renderPage();
		        break;
		    
		    case "delete":
		        $this->deletePerson($_POST[people_id]);
		        $this->action = "list";
		        $this->renderPage();
		        break;
		        
		    case "add":
                $this->message = 'Please enter person data.';
		        $this->formInclude='edit_person';
		        break;
		    
		    case "list":
		        $this->pgtitle = 'All People';
		        $this->listPeople($_POST['ob']);
                $this->formInclude='people_list';
                break;
                
		    default:
                $this->formInclude='people_search';
                break;
        }
    }
    
    static public function getObject($people_id=NULL){
        global $myDB;
        
        $oPerson = new oPerson($people_id);
        
            if(!is_null($people_id)){
                $sql = "SELECT * FROM people WHERE people_id = '$people_id'";
                $results = $myDB->getRow($sql);
                $oPerson = $myDB->assignRowValues($oPerson, $results);
            }
            
        DEBUG((array)$oPerson, 'Person Object:', true, SQL);
        return $oPerson;
    }    
	
	static protected function loadChild($object){
	    global $myDB;
	    
        $sql = "SELECT * FROM people WHERE people_id = '$object->people_id'";
        $results = $myDB->getRow($sql);
        $object = $myDB->assignRowValues($object, $results, 'oPerson');
        return $object;
    }
    
	static protected function getFormObject($people_id=NULL){
	    global $myDB;
	    
	    $oPerson = self::getObject($people_id);
		$oPerson = $myDB->assignFormValues($oPerson);
		return $oPerson;
	}
    
    static protected function saveObject($oPerson){
        global $myDB;
        
        $oPerson->update_date = 'NOW()';
        
        if($oPerson->people_id){
            $sql = $myDB->generateUpdateSQL($oPerson, 'oPerson');
            $myDB->runQueryInsert($sql);
        } else {
            $sql = $myDB->generateInsertSQL($oPerson, 'oPerson');
            $oPerson->people_id = $myDB->runQueryInsert($sql);
        }
        return $oPerson;
    }

    function listPeople($order_by=false) {
        global $myDB;
		
        $sql = "SELECT DISTINCT p.people_id, p.first_name, p.last_name, p.email1, p.update_date 
                FROM people p";
        
            switch($_POST['person_type']){
                
                case 'authors':
                    $sql.= " INNER JOIN paper_authors pa ON(pa.people_id=p.people_id)";
                    break;
                    
                case 'reviewers':
                    $sql.= " INNER JOIN paper_reviewers pr ON(pr.people_id=p.people_id)";
                    break;
                    
                case 'users':
                    $sql.= " INNER JOIN users u ON(u.people_id=p.people_id)";
                    break;
                    
                case 'editors':
                    $sql.= " INNER JOIN users u ON(u.people_id=p.people_id)";
                    $sql.= " INNER JOIN papers pp ON(pp.editor_user_id=u.user_id)";
                    break;
                    
                default:
            
            }
            
            if(isset($_POST['name'])){
                $sql .= " WHERE LOWER(first_name) LIKE '%".strtolower($_POST['name'])."%' OR LOWER(last_name) LIKE '%".strtolower($_POST['name'])."%'";
                $this->pgtitle = 'Search Results';
                $paging_url = "/people.php?mode=list&name=true";
            } else {
                $paging_url = "/people.php?mode=list";
            }

       	$sql .= ($order_by) ? " ORDER BY " . $order_by : " ORDER BY last_name ";
       	
       	$oPager = new Pager(30,$paging_url);
        $this->oTemp = $oPager->getDataSet($sql,'p.people_id');    
        $this->topPaging = $oPager->drawTopPaging(11);
        $this->bottomPaging = $oPager->drawBottomPaging(11);
    }

    private function deletePerson($people_id){
        global $myDB;

        $sql = "SELECT p.people_id
                FROM people p
                LEFT OUTER JOIN users u ON(p.people_id=u.people_id)
                LEFT OUTER JOIN paper_authors a ON(p.people_id=a.people_id)
                LEFT OUTER JOIN paper_reviewers r ON(p.people_id=r.people_id)
                WHERE p.people_id = '$people_id' AND
                (
                    u.people_id IS NOT NULL OR
                    a.people_id IS NOT NULL OR
                    r.people_id IS NOT NULL
                )";
                
        $result = $myDB->getRow($sql);
        
        if($result){
            $this->error_message = 'Person can not be deleted, it is associated to a user, author, or reviewer';
        } else {
            $sql = "DELETE from people WHERE people_id = '$people_id'";
            $this->success_message = 'Person has been deleted from system';
            $myDB->runQueryUpdate($sql);
        }
    }
    
    static function getPersonChoices($first_name, $last_name, $limit=25) {
        global $myDB;
       
        $sql = "
            SELECT people_id, first_name, middle_name, last_name, email1
            FROM people 
            WHERE first_name LIKE '" . substr(addslashes($first_name),0,3) . "%' AND last_name LIKE '" . substr(addslashes($last_name),0,3) . "%'
            UNION DISTINCT
            SELECT people_id, first_name, middle_name, last_name, email1
            FROM people 
            WHERE first_name LIKE '" . substr(addslashes($first_name),0,3) . "%' OR last_name LIKE '" . substr(addslashes($last_name),0,3) . "%'";
        
        $results = $myDB->select($sql);
        $is_contact = ($_POST[is_contact]) ?'(Is Contact Author)':'';
        $checked = (!count($results)) ? ' checked' : '';
        $choices = '<table cellpadding="1" cellspacing="1" border="0" width="100%">
                        <tr>
                            <td class="formfield" colspan="3"><span class="bold">Name: </span>'.$_POST['person_first_name'].' '.$_POST['person_last_name'].' '.$is_contact.'<br>';
        $choices .=  (isset($_POST[person_email]))?"<span class=\"bold\">E-mail:</span>&nbsp;$_POST[person_email]":'';
        $choices .=   '</td>
                        </tr>
                        <tr>
                            <td class="formfield"><input type="radio" name="people_id" value="" '.$checked.'/></td>
                            <td colspan="2" class="formfield" width="100%"><span class="bold">Create New Person</span></td>
                        </tr>';
        
        if(count($results) && is_array($results)){
             $choices .=   '<tr>
                                <td colspan="4" class="formfield">&nbsp;</td>
                            </tr>
                            <tr>
                                <td class="editheader"></td>
                                <td align="center" class="editheader"><span class="bold">Name</span></td>
                                <td align="center" class="editheader"><span class="bold">E-mail</span></td>
                            </tr>';
                $i=0;
                
                foreach($results as $result) {
                    $name = ($result["middle_name"]) ? $result["first_name"]." ".$result["middle_name"]." ".$result["last_name"] : $result["first_name"]." ".$result["last_name"];
                    $choices .= '<tr>';
                    $choices .= "<td class=\"formfield\"><input type=\"radio\" id=\"people_id_" . $result["people_id"] . "\" name=\"people_id\" value=\"" . $result["people_id"] . "\" /></td>\n";
                    $choices .= ($result["first_name"]) ? '<td class="formfield">'.$result["first_name"] . " " . $result["last_name"].'</td>': '<td class="formfield">&nbsp;</td>';
                    $choices .= ($result["email1"]) ? '<td class="formfield">'.$result["email1"].'</td>': '<td class="formfield">&nbsp;</td>';
                    $choices .= '</tr>';    
                    $i++;
                    if($i>$limit) break;
                }
            } else {
                $choices .= '<tr>
                                <td colspan="3" class="formfield" align="center"><span class="bold"> No possible matches were found</span></td>
                            </tr>';
            }
            $choices .= '</table>';
        return $choices;
    }
    
    /*-- build merge array, to be parsed on the presentation template --*/
    static public function processMerge($oPerson,$oSubAuthor){
        $arMerge=array();
        
        /*-- for publishing authors set to person --*/
        if($_POST['is_published']){
            $arMerge['is_published'] = $_POST['is_publish'];
            $arMerge['published_first_name'] = $oSubAuthor->first_name ;
            $arMerge['published_middle_name'] = $oSubAuthor->middle_name ;
            $arMerge['published_last_name'] = $oSubAuthor->last_name ;
            
            $arMerge['first_name'] = $oPerson->first_name ;
            $arMerge['middle_name'] = $oPerson->middle_name ;
            $arMerge['last_name'] = $oPerson->last_name ;
            
        } else {
        
            if(!strlen(trim($oPerson->first_name)) || trim($oSubAuthor->first_name) == trim($oPerson->first_name)){
                $arMerge['first_name'] = $oSubAuthor->first_name ;
            } else {
                $arMerge['first_name'] = array(
                                            "author"=>$oSubAuthor->first_name,
                                            "person"=>$oPerson->first_name
                                         );
            }
            
            if(!strlen(trim($oPerson->middle_name)) || trim($oSubAuthor->middle_name) == trim($oPerson->middle_name)){
                $arMerge['middle_name'] = $oSubAuthor->middle_name ;
            } else {
                $arMerge['middle_name'] = array(
                                            "author"=>$oSubAuthor->middle_name,
                                            "person"=>$oPerson->middle_name
                                         );
            }
            
            if(!strlen(trim($oPerson->last_name)) || trim($oSubAuthor->last_name) == trim($oPerson->last_name)){
                $arMerge['last_name'] = $oSubAuthor->last_name ;
            } else {
                $arMerge['last_name'] = array(
                                            "author"=>$oSubAuthor->last_name,
                                            "person"=>$oPerson->last_name
                                         );
            }
        }
        
        if(!strlen(trim($oPerson->email1)) || trim($oSubAuthor->email) == trim($oPerson->email1)){
            $arMerge['email1'] = $oSubAuthor->email ;
        } else {
            $arMerge['email1'] = array(
                                    "author"=>$oSubAuthor->email,
                                    "person"=>$oPerson->email1
                                 );
        }
        
        if(!strlen(trim($oPerson->address))){
            $arMerge['address'] = $oSubAuthor->address;
        } else {
            $arMerge['address'] = array(
                                        "author"=>$oSubAuthor->address,
                                        "person"=>$oPerson->address
                                     );
        }
        
        /*-- populate other author info --*/
        $arMerge['is_published'] = $_POST['is_published'];
        $arMerge['email2'] = trim($oPerson->email2);
        $arMerge['phone'] = trim($oPerson->phone) ;
        $arMerge['is_contact'] = trim($oSubAuthor->is_contact);
        $arMerge['people_id']= $oPerson->people_id;
        
        return $arMerge;
    }
}
?>