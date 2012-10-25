<?php
/*
 *
 *
 *
 * @author		DWN
 * @copyright	(c) 2005 PROJAS
 * @version     $Id: livesearch.php,v 1.3 2006-02-09 02:13:00 dan Exp $
 *
 */

include_once ('../conf/global.cfg');
include_once (PROJAS_LIBRARY.'livesearch.php');

echo liveSearch($_GET['mode'], $_GET["q"]);
?>