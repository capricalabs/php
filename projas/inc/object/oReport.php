<?php
/*
 *
 *
 * @author		BDM <dogtowngeek@gmail.com>
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: oReport.php,v 1.6 2006-03-06 20:17:06 dan Exp $
 *
 *
 */

class oReport {

    /* report info */

    public $editor_name;
    public $current_paper_count = 0;
    public $pending_paper_count = 0;
    public $total_paper_count = 0;
    public $total_needing_reviewers = 0;
    public $people_id;
    public $editor_id;
    public $is_active;
    
    public $papers=array(); 
    
    public function __construct($report_id=NULL) {
        $this->report_id = $report_id;
    }
}
?>