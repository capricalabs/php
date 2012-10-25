<?php
/*
 * @author		LB, DWN
 * @copyright	(c) 2005 PROJAS 
 *
 */
 
 class Pager {
 
 	private $current_page;
	private $max_per_page;
	private $total_records;
	private $total_pages;
	private $url;
	
	public function __construct($max_per_page,$url) {
		$this->max_per_page = $max_per_page;
		$this->url = $url;
		$this->current_page = ($_GET["page"]) ? $_GET["page"] : 1;
		$this->total_records = $_SESSION['SEARCH_RESULTS']['count'];
	}
	
    function drawPager($max_page_links=false) {
	
		$total_pages = ceil($this->total_records / $this->max_per_page);
		
		// if there is a '?' in the url, then there are already some variables in it
		// use an '&' to separate the page number
		$var_sep = (strpos($this->url,"?") > -1) ? "&" : "?";
		
		/*-- show previous link if not first page --*/
		$previous = ($this->current_page > 1) ? '<a href="'.$this->url.$var_sep.'p='.($this->current_page-1).'" class="paging">Previous</a>&nbsp;&nbsp;' : '';
		/*-- show next link if not last page --*/
		$next = ($this->current_page < $total_pages) ? "&nbsp;&nbsp;<a href=\"".$this->url.$var_sep."page=".($this->current_page+1)."\" class=\"paging\">Next</a>" : '';
		$start_page = 1;
		$end_page = $total_pages;
		// if there is a limit to how many page numbers we should print & it's less than the total number of pages
		if (($max_page_links) && ($max_page_links < $total_pages)) {
			// then display half of $max_page_links below the current page & half above
			$start_page = $this->current_page - ceil($max_page_links/2) + 1;
			$end_page = $this->current_page + floor($max_page_links/2);
			// make sure there are no negative page numbers
			while($start_page < 1) {
				$start_page++;
				$end_page++;
			}
			// make sure there are no page numbers greater than the total
			while($end_page > $total_pages) {
				$start_page--;
				$end_page--;
			}
		}
		// if the start page is greater than 1, print some elipses so the user will know there are more pages
		$page_numbers = '';
		// if this isn't page $i, include a link to page $i, otherwise just print $i
		for ($i=$start_page;$i<=$end_page;$i++) {
			$page_number = ($this->current_page != $i) ? "<a href=\"".$this->url.$var_sep."page=".$i."\" class=\"pagenum\">".$i."</a>&nbsp;" : "<span class=\"pageactive\">".$i."</span>&nbsp;";
			$page_numbers .= $page_number;
		}
		
		return $previous . $page_numbers . $next ;
	}
	
	public function drawTopPaging($max_page_links=false){
	
	    return "<div align=\"center\"><table border=\"0\" cellspacing=\"1\" cellpadding=\"1\" width=\"100%\">
                    <tr>
                        <td align=\"left\" class=\"pagingresults\">".$this->drawSummary($max_page_links)."</td>
                        <td class=\"pagingresults\" align=\"right\">Total Pages: ".$this->total_pages."</td>
                    </tr>
                </table></div>";
	}
	
	public function drawBottomPaging($max_page_links=false){
	
        return "<div align=\"center\"><table border=\"0\" cellspacing=\"1\" cellpadding=\"1\">
                        <tr>
                            <td class=\"text\" align=\"center\">Result Page: " . $this->drawPager($max_page_links) . "</td>
                        </tr>
                    </table></div>";
	}
	
	public function drawSummary($max_page_links) {

		$this->total_pages = ceil($this->total_records / $this->max_per_page);
		$record_end = $this->current_page * $this->max_per_page;
		$record_begin = $record_end - $this->max_per_page + 1;
		$record_end = ($record_end > $this->total_records) ? $this->total_records : $record_end;
		$summary = "Results " . $record_begin . " - ".$record_end." of " . $this->total_records ;
		return $summary;
	}
	
	public function getCurrentPage() {
		return $this->current_page;
	}
	
	public function getMaxPerPage() {
		return $this->max_per_page;
	}
	
	public function setTotalRecords($sql,$distinct,$is_remote=NULL) {
           if(!is_null($is_remote)){
               $myDB = new DB(true);
           } else {
               global $myDB;
           }
		
		$arParts = split("FROM",$sql,2);
		$sql = $arParts[1];
		  if(strstr($sql,"ORDER BY")){
		      $arParts = split("ORDER BY",$sql,2);
		      $sql = $arParts[0];
		  }   
		
		$sql = "SELECT COUNT(DISTINCT $distinct) AS count FROM " . $sql;
        $result = $myDB->getRow($sql);
		$this->total_records = $result["count"];
		$_SESSION['SEARCH_RESULTS']['count'] = $this->total_records ;
	}
	
	public function getDataSet($sql,$distinct,$is_remote=NULL){
	   if(!is_null($is_remote)){
	       $myDB = new DB(true);
	   } else {
	       global $myDB;
	   }
	   
            if(!$_POST['page']){
                $this->setTotalRecords($sql,$distinct,$is_remote);
                $_SESSION['SEARCH_RESULTS']['results'] = $myDB->select($sql);
            }
            
        /*-- determine dataset offset --*/
        $offset = ($this->getCurrentPage() - 1) * $this->getMaxPerPage();
        /*-- set max of data range --*/
        $max_val = $this->getMaxPerPage();
        /*-- filter out from search results --*/
        return array_slice($_SESSION['SEARCH_RESULTS']['results'],$offset,$max_val);
	}
}
?>