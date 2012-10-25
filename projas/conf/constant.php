<?php
/*
 *
 *
 *
 * @author		DWN<dogtowngeek@gmail.com>
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: constant.php,v 1.1.1.1 2006-02-08 02:24:33 dan Exp $
 *
 */

$public_templates = array(
    'header',
    'footer',
);

$arUploadErrors = array(
    0=>"There is no error, the file uploaded with success",
    1=>"The uploaded file exceeds the upload_max_filesize directive in php.ini",
    2=>"The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form",
    3=>"The uploaded file was only partially uploaded",
    4=>"No file was uploaded",
    6=>"Missing a temporary folder"
);

$arPaperDecisions = array(
    ''=>"Under Review",
    1=>"Accepted",
    2=>"Rejected",
    3=>"Rejected--resubmit after revisions",
    4=>"Rejected--Duplicate",
    5=>"Withdrawn",
    6=>"Rejected--by editor"
);

$arSearchDecisions = array(
    7=>"All Papers",
    9=>"Under Review",
    1=>"Accepted--Unpublished",
    8=>"Accepted--Published",
    2=>"Rejected",
    3=>"Rejected--resubmit after revisions",
    4=>"Rejected--Duplicate",
    5=>"Withdrawn",
    6=>"Rejected--by editor"
);
?>