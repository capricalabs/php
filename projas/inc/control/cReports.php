<?php
/*
 *
 *
 *
 * @author      DWN<dogtowngeek@gmail.com>
 * @copyright   (c) 2005 PROJAS
 * @version     $Id: cReports.php,v 1.16 2006-04-18 22:00:16 dan Exp $
 *
 *
 */

include_once (PROJAS_OBJECT.'/oReport.php');

class Report {

    public function compile($results,$report) {
        global $myDB;

        switch ($report) {

            case 'activity':

                if(is_array($results) && count($results)) {
	                $reports = array(
	                    "active"=>array(),
	                    "inactive"=>array()
	                );

                        foreach($results as $report) {
                            if ($report['editor_user_id'] != $editor_id) {     
                                if (isset($editor_id)) {
                                    if($oReport->is_active == '1'){
                                        $reports['active'][] = $oReport;
                                    } else {
                                        $reports['inactive'][] = $oReport;
                                    }
                                }
                                
                                /*-- clean garbage --*/
                                if(!isset($report['people_id']) || !isset($report['editor_user_id']) || !strlen(trim($report['editor_name']))) continue;
                                
                                $editor_id = $report['editor_user_id'];
                                $oReport = new oReport($report_id);
                                $oReport->editor_name = $report['editor_name'];
                                $oReport->people_id = $report['people_id'];
                                $oReport->is_active = $report['active'];
                            }
                            
                            /*-- note confirmed papers, which are under review --*/
                            if (isset($report['date_editor_accepted']) && !strlen(trim($report['decision']))) {
                                $oReport->current_paper_count++;
                            }
                            
                            if (!isset($report['date_editor_accepted'])  && !strlen(trim($report['decision']))) {
                                $oReport->pending_paper_count++;
                            }
                                
                            /*-- query num of years paper has been acknowledged --*/
                            //note: should convert to PHP, why ping db aside from laziness
                            $result = $myDB->getRow("SELECT DATEDIFF(CURDATE(),\"".$report['date_acknowledged']."\")/365 AS ratio");
                            
                            /*-- if within the last 12 months, note it --*/
                            if ($result['ratio'] < 1) {
                                $oReport->total_paper_count++;
                            }
                        }
                        
                    if($report['active']=='1'){
                        $reports['active'][] = $oReport;
                    } else {
                        $reports['inactive'][] = $oReport;
                    }
                }
                
                break;

            case 'status':
                
                $oReport = new oReport();
                $oReport->papers['out_for_review'] = array();
                $oReport->papers['awaiting_pub'] = array();
                $oReport->papers['prior'] = array();
                
                    if(is_array($results) && count($results)) {
                        foreach($results as $result) {
                            $oPaper = Paper::getObject($result['paper_id']);
                            
                                if (!isset($oPaper->date_editor_accepted) && !strlen(trim($oPaper->decision))) {
                                    /*-- unconfirmed --*/
                                    $oReport->pending_paper_count++;
                                    continue;
                                } elseif (isset($oPaper->date_editor_accepted) && !strlen(trim($oPaper->decision))) {
                                    /*-- out for review --*/
                                    $oReport->papers['out_for_review'][] = $oPaper; 
                                        if (count($oPaper->reviewers) < 3  && !strlen(trim($oPaper->decision))) {
                                            /*-- needs reviewer(s) --*/
                                            $oReport->total_needing_reviewers++;
                                        }
                                } elseif (isset($oPaper->date_editor_accepted) && $oPaper->decision=="1" && !isset($oPaper->is_published)) {
                                    /*-- awaiting publication --*/
                                    $oReport->papers['awaiting_pub'][] = $oPaper; 
                                } else {
                                    /*-- prior papers --*/
                                    $oReport->papers['prior'][] = $oPaper; 
                                }
                        }
                        
                        $oUser = User::getObject($oPaper->editor_user_id);
                        $oReport->editor_name = $oUser->first_name.' '.$oUser->last_name;
                        $oReport->editor_id = $oPaper->editor_user_id;
                        return $oReport;
                    } 
                break;
            }
            
        return $reports;
    }

    static function editorActivity(){

        $sql = "SELECT p.editor_user_id,
                    CONCAT(pe.first_name,' ',pe.last_name) AS editor_name,
                    pp.published_paper_id,
                    p.decision,
                    p.date_editor_accepted,
                    pe.people_id,
                    p.date_acknowledged,
                    u.active,
                    p.paper_id
                FROM papers p
                LEFT OUTER JOIN published_papers pp ON (pp.paper_id=p.paper_id)
                LEFT OUTER JOIN users u ON (p.editor_user_id=u.user_id)
                LEFT OUTER JOIN user_types ut ON (u.user_type_id=ut.user_type_id)
                LEFT OUTER JOIN people pe ON (pe.people_id=u.people_id)
                ORDER BY editor_name";

        return $sql;
    }

    static function editorStatus($people_id) {
    
        $sql = "SELECT DISTINCT p.paper_id,pp.published_paper_id
                    FROM papers p
                    LEFT OUTER JOIN published_papers pp ON (pp.paper_id=p.paper_id)
                    LEFT OUTER JOIN users u ON (p.editor_user_id=u.user_id)
                    LEFT OUTER JOIN people pe ON (pe.people_id=u.people_id)
                WHERE pe.people_id = '$people_id'
                ORDER BY p.paper_id";

        return $sql;
    }
}
?>