<?php
/*
 *
 *
 *
 * @author		DWN<dogtowngeek@gmail.com>
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: cMedia.php,v 1.13 2006-03-03 17:34:58 dan Exp $
 *
 *
 */

include_once (PROJAS_OBJECT.'/oMedia.php');

class Media {
    
    private static function manageUpload($oMedia){
        $file_name = self::getFilePath($oMedia);
        
        if($oMedia->paper_id){
            $target_dir = PROJAS_MEDIA . '/' . $oMedia->paper_id.'/';
        } else {
            $target_dir = PROJAS_MEDIA .'/submissions/'.$oMedia->submission_id.'/';
        }
        
        /*-- create directory if it isn't there --*/
        if(!is_dir($target_dir)){
            mkdir($target_dir, 0777);
        }
        
        $target_path = $target_dir.$file_name;
    
        if(move_uploaded_file($_FILES['fileupload']['tmp_name'], $target_path) === false) {
             throw new Exception("There was a problem moving file upload.");
        }
            
        return $oMedia;
    }
    
    static public function getFilePath($oMedia){
        if($oMedia->is_published){
            $file_name = 'live-'.$oMedia->paper_id.'-'.$oMedia->media_item_id.'-jair.'.$oMedia->file_ext;
            return $file_name;
        } elseif($oMedia->paper_id && $oMedia->file_ext){
            $file_name = 'p'.$oMedia->paper_id.'-'.$oMedia->media_item_id.'-jair.'.$oMedia->file_ext;
            return $file_name;
        } elseif ($oMedia->submission_id && $oMedia->file_ext){
            $file_name = 's'.$oMedia->submission_id.'-'.$oMedia->media_item_id.'-jair.'.$oMedia->file_ext;
            return $file_name;
        } else {
            return false;
        }
    }
    
    static public function getMediaItems($paper_id=NULL,$submission_id=NULL,$is_remote=NULL,$is_publish=NULL){
        if(!is_null($is_remote)){
            $myDB = new DB(true);
        } else {
            global $myDB;
        }
        
        $arMediaItems = array();
        if(!is_null($paper_id) || !is_null($submission_id)){
                
            if(!is_null($paper_id)){
                $sql = "SELECT media_item_id FROM media_items WHERE paper_id = '$paper_id'";
            } else {
                $sql = "SELECT media_item_id FROM media_items WHERE submission_id = '$submission_id'";
            }    
            
            if(!is_null($is_publish)){
                $sql .= " AND is_published = '1'";
            } else {
               $sql .= " AND (is_published IS NULL OR is_published != '1')";
            }
                
            $results = $myDB->select($sql);
            
            if($results){
                foreach($results as $result){
                    $oMedia = self::getMediaItem($result[media_item_id], $is_remote);
                    $arMediaItems[] = $oMedia;
                }
            }
            
            return $arMediaItems;
        } else {
            return false;
        }
    }
    
    static public function getMediaItem($media_item_id=NULL,$is_remote=NULL){
        if(!is_null($is_remote)){
            $myDB = new DB(true);
        } else {
            global $myDB;
        }
        
        $oMedia = new oMedia();
        
        if(!is_null($media_item_id)){
            $sql = "SELECT mi.*
                    FROM media_items mi
                    WHERE media_item_id = '$media_item_id'
                    ";
            $result = $myDB->getRow($sql);
            
                if($result){
                    $oMedia = $myDB->assignRowValues($oMedia, $result);
                    DEBUG((array)$oMedia, 'Initialize Media Object:', true, SQL);  
                }
                    
        } else {
            $oMedia->paper_id = $_POST[paper_id];
            $oMedia->is_public = '1';
        }
        
        return $oMedia;
    }
    
    public static function saveMedia($oMedia){
        global $myDB;
        
        $oMedia->date_updated = 'NOW()';
        /*-- treat urls like html for publishing --*/
        if(strlen(trim($oMedia->document_url))){
            $oMedia->file_type_id = '12';
        }
        
        if($oMedia->media_item_id){         
            $sql = $myDB->generateUpdateSQL($oMedia);
            $myDB->runQueryUpdate($sql);
            $is_update = true;
        } else {
                /*-- return if no file uploaded --*/
                if($oMedia->transfer_error && !strlen(trim($oMedia->document_url))){
                    return $oMedia;
                }
                
            $oMedia->date_added = 'NOW()';
            $sql = $myDB->generateInsertSQL($oMedia);
            $oMedia->media_item_id = $myDB->runQueryInsert($sql);
        }
        
        if(!$oMedia->transfer_error && count($_FILES)){
/*
delete existing files?
how to, since oMedia is
tweaked in form object

                if($is_update){
                    self::deleteFile($oMedia);
                }
*/
            $oMedia = self::manageUpload($oMedia);
        }
        return $oMedia;
    }
    
    static public function deleteMediaItem($oMedia){
        global $myDB;
        
        $sql = "DELETE FROM media_items 
                WHERE media_item_id = '$oMedia->media_item_id'";
        $myDB->runQuerySelect($sql);
            if($oMedia->file_size){
                self::deleteFile($oMedia);
            }
    }
    
    static private function deleteFile($oMedia){
        $file_name = self::getFilePath($oMedia);
            if($oMedia->paper_id){
                $target_dir = PROJAS_MEDIA .'/'.$oMedia->paper_id.'/';
            } else {
                $target_dir = PROJAS_MEDIA .'/submissions/'.$oMedia->submission_id.'/';
            }
            
        $target_path = $target_dir.$file_name;
        unlink($target_path);
    }
    
    static public function getIcon($oMedia){
        global $myDB;
        
        $sql = "SELECT icon
                FROM file_types
                WHERE file_type_id = '$oMedia->file_type_id'";
                
        $result = $myDB->getRow($sql);
        
            if($result){
                $image = $result[icon];
            } elseif ($oMedia->document_url){
                $image = 'htmlicon.gif';
            } else {
                $image = 'unknownicon.gif';
            }
        return '<img src="/images/'.$image.'" width="16" height="16" border="0">';
    }
    
    function getFormMediaObject($media_item_id=NULL){
        global $myDB;
        
        $oMedia = self::getMediaItem($media_item_id);
		$oMedia = $myDB->assignFormValues($oMedia);
		$oMedia->is_public = $_POST['is_public'];
		$oMedia->transfer_error = $_FILES[fileupload][error];

		    if(!$oMedia->transfer_error && count($_FILES)){
                $oMedia->file_size = round($_FILES[fileupload][size]*.001, 1).'kb';
                $oMedia->media_type = $_FILES[fileupload][type];
                $oMedia->paper_id = $_POST[paper_id];
                $oMedia->file_ext = substr(strstr(basename( $_FILES['fileupload']['name']), "."),1);
                $oMedia->file_type_id = self::setFiletype($oMedia);
                $oMedia->document_url = NULL ;
            }
            
		return $oMedia;
    }
    
    static public function hasPublicAttachments($oPaper){
        
        if(count($oPaper->media)){
            foreach($oPaper->media AS $oMedia){
                if($oMedia->is_public){
                    return true;
                }
            }
        }
        return false;
    }
    
    static public function setFileType($oMedia){
        global $myDB;
        
        $sql = "SELECT file_type_id 
                FROM file_types
                WHERE FIND_IN_SET('$oMedia->file_ext',ext)";
        
        $result = $myDB->getRow($sql);

            if($result){
                return $result[file_type_id];
            } else {
                return false;
            }
    }
    
    static public function getMediaTypes($is_published){
        global $myDB;
        
        $sql = "SELECT media_type_id, media_type, description
                FROM media_types WHERE ";
        
        $sql.= ($is_published) ? "is_publish = '1'" : "is_review = '1'";

        $results = $myDB->select($sql);
        return $results;
    }
    
    static public function getMediaType($oMedia){
        global $myDB;
        
        $sql = "SELECT media_type
                FROM media_types
                WHERE media_type_id = '$oMedia->media_type_id'";
                
        $result = $myDB->getRow($sql);
        return $result['media_type'];    
    }
    
    static public function getFileLink($oMedia,$is_published=NULL){
        
        if ($oMedia->document_url){
            if(!is_null($is_published)){
                $link = $oMedia->document_url;
            } else {
                $link = '<a href="'.$oMedia->document_url.'" onclick="window.open(this.href);return false;">'.vAdmin::padString(str_replace("http://","",$oMedia->document_url), 20).'</a>';
            }
        } elseif(!is_null($is_published)){
            $link = '/media/'.$oMedia->paper_id.'/'.Media::getFilePath($oMedia,$is_publish);
        } elseif(self::getFilePath($oMedia) && !$oMedia->document_url){
            $sub_dir = ($oMedia->paper_id) ? $oMedia->paper_id : 'submissions/'.$oMedia->submission_id;
            $link = '<a href="/media/'.$sub_dir.'/'.Media::getFilePath($oMedia,$is_publish).'" onclick="window.open(this.href);return false;">'.Media::getFilePath($oMedia,$is_publish).'</a>';
        } else {
            $link = 'None';
        } 
        return $link;
    }
    
    static private function processFile($oMedia){
        $source_file = self::getFilePath($oMedia);
    }
    
   /*
    *   when accepting a submission, sync media files and db records into app server
    *   @note: files should be moved to app server every 5 minutes w/rsync 
    */
    static public function processMedia($oPaper,$is_publish=NULL){
        $source_path = PROJAS_MEDIA . '/submissions/'.$oPaper->submission_id . '/';
        $target_path = PROJAS_MEDIA .'/'.$oPaper->paper_id.'/';
            /*-- create directory if it isn't there --*/
            if(!is_dir($target_path)){
                mkdir($target_path, 0777);
            }
        /*-- move media objects from submission to application db--*/
        $arMedia = self::getMediaItems(NULL, $oPaper->submission_id, 'is_remote');

            if(is_array($arMedia) && count($arMedia)){
                foreach($arMedia AS $oMedia){
                    $source_file = self::getFilePath($oMedia);
                    $oMedia->media_item_id = NULL;
                    $oMedia->paper_id = $oPaper->paper_id;
                    $oMedia->is_published = $is_publish;
                    $oMedia->is_public = '1';
                    $oMedia = self::saveMedia($oMedia);
                    $target_file = self::getFilePath($oMedia,$is_publish);

                        if($oMedia->file_size){
                            $source = $source_path.$source_file;
                            $target = $target_path.$target_file;
                            /*-- copy files from submission to paper file system --*/
                            if(copy($source, $target) === false){
                                DEBUG($source.' to<br>'.$target, 'File Transfer Failure:', true, ERROR);
                            } else {
                                DEBUG($source.' to<br>'.$target, 'File Transfer:', true, SQL);
                            }
                        }
                }
            }
    }
}
?>