<?php
/*
 *
 *
 *
 * @author       DWN<dogtowngeek@gmail.com>
 * @copyright    (c) 2005 PROJAS
 * @version      $Id: cPaperSearch.php,v 1.3 2006-03-02 19:09:06 dan Exp $
 * 
 *
 */

    class cSearchPapers {
        
        /**
         * Name of table containing message
         * @var     string
         * @access  private
         */
        static function buildQuery(&$is_publish=false) {
            
            global $_POST;

            /*-- begin query --*/
            $sql = "SELECT DISTINCT p.paper_id
                    FROM papers p ";
            $sql .= "LEFT OUTER JOIN published_papers pp ON (p.paper_id=pp.paper_id) ";

            
            $arWhere = array();

            /*-- publication range --*/
            if ($_POST['published_from_Date']) {
                $arWhere[] = "pp.publish_date BETWEEN '$_POST[published_from_Date]' AND '$_POST[published_to_Date]'";
                $is_publish=true;
            }
            
            /*-- filter by published volume --*/
            if ($_POST['volume']){
                $arWhere[] = "pp.volume = '$_POST[volume]'";
                $is_publish=true;
            }
            
            if($_POST['filter']){
                switch($_POST[filter]) {
                    
                    case 'overdue':
                        $arWhere[] = 'pr.date_received IS NULL';
                        $arWhere[] = 'pr.date_due >= NOW()';
                        break;
                    
                    case 8:
                        $arWhere[] = "pp.is_active = '1'";
                        $arWhere[] = "p.decision= '1'";
                        $is_publish=true;
                        break;
                        
                    case 1:
                        $arWhere[] = "p.decision= '1'";
                        $arWhere[] = "(pp.is_active != '1' OR pp.is_active IS NULL)";
                        break;
                        
                    case 2:
                    case 3:
                    case 4:
                    case 5:
                    case 6:
                    
                        $arWhere[] = "p.decision= '$_POST[filter]'";
                        break;
                        
                    case 7:
                        break;
                        
                    default:
                        $arWhere[] = "(p.decision IS NULL OR p.decision = '0')";
                        $arWhere[] = "pp.published_paper_id IS NULL";
                }
            }
            
            if($_POST[review_time]){
                $sql .= "LEFT OUTER JOIN paper_reviewers pr ON (pr.people_id=U.people_id) ";
                
                switch($_POST[review_time]) {
                    
                    case 'less_than':
                    
                        $arWhere[] = "(DATE_SUB(p.date_editor_accepted, INTERVAL '$_POST[rev_years] $_POST[rev_months]' YEAR_MONTH) < p.date_received)";
                        break;
    
                    case 'greater_than':
                    
                        $arWhere[] = "(DATE_SUB(p.date_editor_accepted, INTERVAL '$_POST[rev_years] $_POST[rev_months]' YEAR_MONTH) > p.date_received)";
                        break;
    
                    case 'equal_to':
                    
                        $arWhere[] = "(DATE_SUB(p.date_editor_accepted, INTERVAL '$_POST[rev_years] $_POST[rev_months]' YEAR_MONTH) = p.date_received)";
                        break;
    
                    default:
                }
            }
            
            $arWhere[] = (strlen(trim($_POST[key_word]))) ? "LOWER(p.notes) LIKE '%".strtolower(trim($_POST[key_word]))."%'" : NULL;
            
            /*-- filter by human-being --*/
            if($_POST[people_id]){
                $sql .= "LEFT OUTER JOIN paper_authors pa ON (pa.paper_id=p.paper_id) ";
                $arWhere[] = "pa.people_id='$_POST[people_id]'";
            }

            /*-- filter by title --*/
            $arWhere[] = ($_POST[title]) ? "LOWER(p.title) LIKE '%".strtolower(trim($_POST[title]))."%'" : NULL;
            
            /*-- strip out null and dupe filters --*/
            $arWhere = array_unique(array_diff($arWhere, array('')));

            $sWhere = (count($arWhere)) ? " WHERE ".implode(" AND ",$arWhere) : "" ;
            return $sql.$sWhere;
        }

    }
?>