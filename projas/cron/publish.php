<?php
/*
 *
 *
 *
 * @author		DWN<dogtowngeek@gmail.com>
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: publish.php,v 1.21 2006-04-06 21:07:27 dan Exp $
 *
 *
 */

/*-- local db --*/
define ('DBUSERNAME', 'username');
define ('DBPASSWORD', 'password');
define ('DBHOST', 'localhost');
define ('DBDATABASE', 'database');

define ('PROJAS_BASE', dirname(__FILE__).'/..');
define ('PROJAS_LIBRARY', PROJAS_BASE.'/inc/lib/');
define ('PROJAS_CONTROL', PROJAS_BASE.'/inc/control/');
define ('PROJAS_OBJECT', PROJAS_BASE.'/inc/object/');
define ('PEAR_LIBRARY', PROJAS_LIBRARY.'PEAR/');

include_once (PROJAS_CONTROL.'cMasterController.php');
include_once (PROJAS_BASE.'/inc/lib/db.php');
include_once (PROJAS_BASE.'/inc/lib/htmlMimeMail5/htmlMimeMail5.php');
include_once (PROJAS_CONTROL.'/cPublish.php');
include_once (PROJAS_LIBRARY.'/util.php');

$myDB = new DB();

if(cPublishFactory::rsyncSite()===true){

    $message = "New PROJAS Site revision has been pushed live";
    
    $mail = new htmlMimeMail5();
    $mail->setFrom("PROJAS Publishing<autopub@jair.com>");
    $mail->setSubject("PROJAS Publication Report");
    $mail->setPriority('medium');
    $mail->setText($message);
        $arSendTo = array(
            "Steve Minton<sminton@fetch.com>"
        );
        
    $mail->send($arSendTo);
}
?>