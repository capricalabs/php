<?php
/*
 *
 *
 *
 * @author		DWN<dogtowngeek@gmail.com>
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: cPublishFactory.php,v 1.177 2006-04-03 07:36:56 dan Exp $
 *
 *
 */
 include_once (PROJAS_LIBRARY.'/publish.php');
 include_once (PROJAS_CONTROL.'cPublish.php');
 include_once (PROJAS_CONTROL.'cMedia.php');
 
 class cPublishFactory {
    
   /*
    *  Private Properties
    */

    private $content1;
    private $startTime;
    private $static_dir;
    private $start_time;
    
   /*
    *  Public Properties
    */
    public $success = false;
    public $sError;
    public $execute_time;

    function __construct(){
        $this->start_time = time();
        $this->static_dir = PUBLIC_TEMPLATES_PATH.'static/';
    }
    
   /*
    *
    */
    public function commencePublish(){
        global $myDB;
        
        $this->assemblyLine();
        $this->setPublishTime();
        
        $sql = "INSERT INTO published_log (execution_time, regenerated_date) 
                VALUES ('$this->execute_time',NOW())";
                
        $myDB->runQueryInsert($sql);
        
        return true;    
    }
    
    private function setPublishTime(){
        $this->execute_time = time() - $this->start_time;
    }
    
    static public function getMaxVolume(){
        global $myDB;
        $sql = "SELECT max(volume) as volnum
                FROM published_papers";
        $result = $myDB->getRow($sql);
        return $result['volnum'];
    }
    
    private function drawVolumeSelect(){
        $i = self::getMaxVolume();
        $sSelect="\n<p><select name=\"volume_select\" onchange=\"window.location='/vol/vol' + this.value + '.html'\">";
        $sSelect.="\n<option value=\"\">Choose Archive</option>";
            while($i > 0){
                $sSelect.="\n<option value=\"$i\">Volume $i:".$this->getVolDateRange($i)."</option>";
                $i--;
            }
        $sSelect.="\n</select></p>";
        return $sSelect;
    }   
    
    private function drawTOC(){

        global $myDB;
        $sql = "SELECT max(volume) as volnum
                FROM published_papers";
                
        $result = $myDB->getRow($sql);
        $sTOC = "\n<h1 class=\"odd\">Contents By Volume</h1>";
        $sTOC .="\n".'<ul>';
        $i=$result[volnum];

            while($i > 0){
                $class = ($i % 2 == 1) ? ' class="odd"': '';
                    $link = ($this->bWithAnchor)?"#v$i":"vol/vol$i.html";
                    $sTOC .= "\n<li".$class."><a href=\"$link\">Volume $i</a>: ".self::getVolDateRange($i)."</li>";
                $i--;
            }

        $sTOC.="\n</ul>";

        return $sTOC;
    }

   /*
    * builds individual paper component 
    *
    */
    public static function getVolDateRange($volume_id){
        global $myDB;
        
        $sql = "SELECT MAX(publish_date) as maxdate,MIN(publish_date) as mindate 
                FROM published_papers 
                WHERE volume = '$volume_id'";
                
        $date = $myDB->getRow($sql);
        
        $sDateEnd = vAdmin::formatDate($date['maxdate']);
        $sDateBegin = vAdmin::formatDate($date['mindate']);

        return $sDateBegin.'-'.$sDateEnd;
    }
    
   /*
    * builds individual paper component 
    *
    */
    private function drawPaper($paper_id, $class, $with_abstract=false){

        $oPubPaper = cPublish::getObject($paper_id);
        $sPaper ="\n<div$class>";
        $sPaper .= self::drawPaperTitle($oPubPaper);
        $sPaper .= $this->drawMedia($oPubPaper,$with_abstract);    
            
            if($with_abstract){
                $sPaper .= "\n<p>".nl2br($oPubPaper->abstract_published)."</p>" ;
                $sPaper .= "\n<a href=\"/vol/vol".$oPubPaper->volume.".html\">Click here to return to Volume $oPubPaper->volume contents list</a>";
            }
        
        $sPaper.="\n</div>";
        return $sPaper;
    }
    
    public static function drawPaperTitle($oPubPaper,$hot_link=false,$format=true){
        if($hot_link){
            $hot_begin="<a href=\"/papers/paper$oPubPaper->paper_id.html\">";
            $hot_end="</a>";
        }
        
        $sPaper = trim(vAdmin::drawAuthors($oPubPaper->authors,false,true).' ('.vAdmin::drawYear($oPubPaper->publish_date).') "'.$hot_begin.$oPubPaper->title_published.$hot_end.'", Volume '.$oPubPaper->volume.', pages '.$oPubPaper->start_page.'-'.$oPubPaper->end_page);
        $sPaper = ($format) ? "\n<cite>".$sPaper."</cite>\n" : $sPaper;
        return $sPaper;
    }
    
   /*
    * splices mastHead into volume pages
    * @note: future enhancement->make dynamic
    *
    */
    private function drawMastHead($volume_id,$oPublish){

        $oPublish->rel_path = '/vol/';
        $static_dir = PUBLIC_TEMPLATES_PATH.'mast/';
        $source = fopen($static_dir.'mast'.$volume_id.'.html','r');
        $sMast = fread($source, 1024*1024);
        fclose($source);
        $oPublish->destFile = 'mast'.$volume_id.'.html';
        $oPublish->publishHTML($sMast,true);
    }
    
   /*
    * builds individual volume component
    *
    */
    private function drawVolume($volume_id){
        global $myDB;
        
        $sql = "SELECT p.paper_id 
                FROM papers p 
                INNER JOIN published_papers pp ON (p.paper_id=pp.paper_id)
                WHERE pp.volume = '$volume_id' AND
                pp.is_active='1'
                ORDER by pp.start_page ASC";
        
        $results = $myDB->select($sql);
        $sVolume = "\n<div class=\"volumecontents\">";   
        $sVolume .= "\n<h1 id=\"v$volume_id\">PROJAS Volume $volume_id Articles</h1>";
        $sVolume .= "\n<p><a href =\"/vol/mast$volume_id.html\">Volume $volume_id MastHead</a></p>";
        $sVolume .= "\n<p>Each entry in this table of contents provides links to an individual article and its appendices (if any):</p>";
                       
            if(is_array($results) && count($results)){
                $i=0;
                foreach($results as $paper){
                    $class = ($i % 2 == 1) ? ' class="odd"': '';
                    $class = ($i==0) ? $class.' style="border-top: 2px #CCCCCC ridge;"' : $class;
                    $sVolume .= $this->drawPaper($paper[paper_id],$class);
                    $i++;
                }
        $sVolume .= "\n</div>";
            } else {
                $sVolume .= "\n".'<span class="error">No Papers</span>';
            }
        return $sVolume;
    }
    
   /*
    * draw media items for paper
    *
    */
    private function drawMedia($oPubPaper,$is_abstract=false){
        global $myDB;
        
        $sMedia ='';
        if(is_array($oPubPaper->media) && count($oPubPaper->media)){
            
            /*-- for the volume list page --*/
            
                $arMediaTypes = array(
                        "postscript"=>5,
                        "pdf"=>2,
                        "html"=>12,
                        "htm"=>12
                );
                
                $appendices=array();
                
                    foreach($arMediaTypes AS $var=>$mediatype){
                            foreach($oPubPaper->media AS $oMedia){
                                if($oMedia->file_type_id == $mediatype && $oMedia->media_type_id != 2){
                                    $$var = Media::getFileLink($oMedia,true);
                                    continue;
                                }
    
                                if($oMedia->media_type_id == 2 && !$appendicies_set){
                                    $appendicies[] = array(
                                        "link" => Media::getFileLink($oMedia,true),
                                        "text" => $oMedia->notes
                                    );
                                } 
                            }
                        $appendicies_set=true;
                    }
                
                $sAbstract .= "<a href=\"/papers/paper$oPubPaper->paper_id.html\">Abstract</a>&nbsp;|&nbsp;";
                $sMedia .= "<a href=\"$pdf\">PDF</a>&nbsp;|&nbsp;<a href=\"$postscript\">PostScript</a>";
                $sMedia .= ($html) ? "&nbsp;|&nbsp;<a href=\"$html\" onclick=\"window.open(this.href);return false;\">HTML</a>" : "";
                           
                    /*-- append any and all appendicies --*/
                    $app_cnt = count($appendicies);
                    if($app_cnt){
                        $sMedia .= "\n<br/>";
                        $i=0;
                        foreach($appendicies AS $appendicie){
                            $cnt = ($app_cnt>1) ? $i+1: '';
                            $sMedia .= ($i>0) ? '&nbsp;|&nbsp;' : '';
                            $sMedia .= "<a href=\"$appendicie[link]\">Appendix $cnt</a> - $appendicie[text]";
                            $i++;
                        }
                    }
                    
                    if(!$is_abstract){
                        $sMedia = $sAbstract.$sMedia;
                    }
                    
                $sMedia= '<p class="media">'.$sMedia.'</p>';
        } else {
            $sMedia .= "\n<p><span class=\"error\">None</span></p>";
        }
        
        return $sMedia;
    }
    
    private function drawPaperHeadline(){
        global $myDB;
        $sql = "SELECT p.paper_id 
                FROM papers p 
                INNER JOIN published_papers pp ON(p.paper_id=pp.paper_id)
                WHERE pp.is_active='1'
                ORDER BY pp.volume DESC, pp.start_page DESC
                LIMIT 3";
        $results = $myDB->select($sql);
        $sHeadline='';
            foreach($results AS $result){
                $oPubPaper = cPublish::getObject($result['paper_id']);
                $sHeadline.= "\n".self::drawPaperTitle($oPubPaper,true);
            }
        return $sHeadline;
    }
    
   /*
    * publish individual papers
    *
    */
    private function assemblyLine(){
        global $myDB;
     
        $oPublish = new publish();
        $oPublish->base_dir = PROJAS_PUBLIC;
        $oPublish->sidebar = "\n<div id=\"sidebar\">"
                                    ."\n<div id=\"tophalf\">".$oPublish->snippets['sidebar_top.tmpl']."</div>"
                                    .$oPublish->snippets['sidebar_bot.tmpl']
                            ."\n</div>";
                              
       /*
        * copy *live* media to public dir
        *
        */
        shell_exec("rsync -rav --include \"*/\" --include \"live*\" --exclude \"*\" --delete ".PROJAS_MEDIA."/ ".PUBLIC_MEDIA_PATH);

       /*
        * home page
        */
        $sHome = $oPublish->snippets['home_text.tmpl'];
        $sHome .= "\n<p><a href=\"/vol/vol".$this->getMaxVolume().".html\">Click here for latest volume</a> &raquo;&raquo;</p>";
        $sHome .= "\n<h2>Recently Published Articles:</h2>";
        $sHome .= "\n<div  class=\"abstract\">".$this->drawPaperHeadline()."\n</div>";
        $sHome .= "\n<div style=\"height: 50px;\"></div>";
        $oPublish->destFile = 'index.html';
        $oPublish->publishHTML($sHome,true);

       /*
        * publish individual papers
        *
        */
        $oPublish->rel_path = 'papers/';

        $sql = "SELECT p.paper_id 
                FROM papers p 
                INNER JOIN published_papers pp ON(pp.paper_id=p.paper_id)
                WHERE pp.is_active = '1'
                ORDER BY p.paper_id";
             
        $results = $myDB->select($sql);
            if(count($results)){
                foreach($results as $result){
                    $this->content1 = $this->drawPaper($result[paper_id],' class="abstract"',true);
                    $oPublish->destFile = 'paper'.$result[paper_id].'.html';
                    $oPublish->publishHTML($this->content1);
                }
            }
        
       /*
        * publish individual volumes and mastheads
        *
        */
        $oPublish->rel_path = 'vol/';
        unset($this->content1);
            for($k=1; $k<=$this->getMaxVolume(); $k++){
                $this->content1 = $this->drawVolume($k);
                $this->drawMastHead($k,$oPublish);
                $oPublish->destFile = 'vol'.$k.'.html';
                $oPublish->publishHTML($this->content1);
            }
       
       /*
        * publish static pages
        *
        */

        $oPublish->rel_path = '/';
        
        $files = scandir($this->static_dir);
            for($i=2; $i<count($files);$i++){            
                //ignore CVS directories
                if(strstr($files[$i], 'CVS')) continue;
                $source = fopen($this->static_dir.$files[$i],'r');
                $static_source = fread($source, 1024*1024);
                $oPublish->destFile = $files[$i];
                $oPublish->publishHTML($static_source,true);
                fclose($source);
            }
        $this->success = true;
        return;
    }
    
    static public function rsyncSite(){
        global $myDB;
        
        $sql = "SELECT published_log_id,is_active,is_transfered
                FROM published_log
                WHERE published_log_id = (SELECT max(published_log_id)
                FROM published_log)";
        $result = $myDB->getRow($sql);
        
            if($result['is_active'] == "1" && $result['is_transfered'] != "1"){
                /*-- rsync web file system with remote server --*/
                shell_exec("rsync -e ssh -avzp /home/dan/jair/jair.public/ minton@boston.eecs.umich.edu:/n/www/y/www-jair/www-jair/");
                
                /*-- update published log (unflag) --*/
                $sql = "UPDATE published_log
                        SET is_transfered = '1',
                            transfer_date = NOW()
                        WHERE
                            published_log_id = '$result[published_log_id]'";
                
                $myDB->runQueryUpdate($sql);
                return true;
            }
        return false;
    }
 }
?>