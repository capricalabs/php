<?php
/*
 *
 *
 *
 * @author		DWN
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: publish.php,v 1.18 2006-02-23 03:12:04 dan Exp $
 *
 * @notes: Permissions in the public directory must be set to 'nobody' 
 * and chmoded 777 in order for this script to update and create the 
 * necessary files
 *
 */
 
 class publish {
    
    private $header;
    private $footer;
    private $temp_path = PUBLIC_TEMPLATES_PATH; 
    private $public_templates;
    private $snippet_dir;
            
    public $source;
    public $destFile;
    public $base_dir;
    public $rel_path;
    public $pgContent;
    public $sidebar;
    public $snippets=array();
    
    public function __construct(){
        global $public_templates;
        
        $this->public_templates = $public_templates;
        $this->snippet_dir = PUBLIC_TEMPLATES_PATH.'snippets/';
        $this->loadTemplates();
        $this->loadSnippets();
    }

    public function publishHTML($contents,$include_sidebar=false){

        $is_fullhtml = strstr($this->destFile, 'html');
            //include header, nav, and footer if the template
            //type is html, otherwise only publish the exact content
            if($is_fullhtml){
                $this->pgContent = $this->header."\n";
                $css_slug = (!$include_sidebar) ? "\n<link rel=\"stylesheet\" type=\"text/css\" href=\"/no_sidebar.css\" />" : "";
                $this->pgContent = str_replace("%more_calls%", $css_slug, $this->pgContent);
            }
            
        $this->pgContent .= $contents;
            if($is_fullhtml){
                $this->pgContent .= "\n</div>";
            }
        
            if($include_sidebar && $is_fullhtml){
                $this->pgContent .= $this->sidebar;
            }
            
            if($is_fullhtml){
                $this->pgContent .= "\n".$this->footer;
            }
            
        $file = fopen($this->base_dir.$this->rel_path.$this->destFile, 'w');
        fwrite($file,$this->pgContent);
        fclose($file);
        unset($this->pgContent);
    }
    
    private function loadTemplates(){
        //initiate file resources
        foreach($this->public_templates as $template){
            $source = fopen($this->temp_path.$template.'.tmpl','r');
            $this->$template = fread($source, 1024*1024);
            fclose($source);
        }
    }
    
    private function loadSnippets(){
        $files = scandir($this->snippet_dir);
            for($i=2; $i<count($files);$i++){            
                //ignore CVS directories
                if(strstr($files[$i], 'CVS')) continue;
                $source = fopen($this->snippet_dir.$files[$i],'r');
                $snippet = fread($source, 1024*1024);
                $this->snippets[$files[$i]] = $snippet;
                fclose($source);
            }
    }
 }
?>