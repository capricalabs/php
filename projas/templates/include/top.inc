<?

/*
 *
 *
 *
 * @author		DWN<dogtowngeek@gmail.com>
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: top.inc,v 1.1.1.1 2006-02-08 02:24:33 dan Exp $
 *
 * @notes: 
 */
 
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
    <title>PROJAS Admin::<?=str_replace("<br>", "", "$myPM->pgtitle")?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <link rel="STYLESHEET" type="text/css" href="/css/styles.css">
    <script language="javascript" type="text/javascript" src="/js/common.js"></script>

<script>
    if(is_ie){
        document.write('<link rel="STYLESHEET" type="text/css" href="/css/ie_styles.css">');
    } else {
        document.write('<link rel="STYLESHEET" type="text/css" href="/css/mozilla_styles.css">');
    }
</script>

    <script language="javascript" type="text/javascript" src="/js/livesearch.js"></script>
    
    <script language="javascript" type="text/javascript" src="/js/date.js"></script>
</head>
<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" rightmargin="0" bottommargin="0">
<form method="post" name="jump_to" action="/paper.php">
    <input type="hidden" name="mode" value="paper_details"> 
<div align="center">
<table cellpadding="0" cellspacing="0" border="0" width="750" height="100%" class="parent-table">
    <tr>
        <td colspan="2" height="30">
            <table cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td>
<?     if($_SESSION['user_id']){ 
            $header = (cLogin::hasPermission()) ? 'header_admin.jpg' : 'header_editors.jpg';
?>
            <img src="/images/<?=$header?>" border="0" width="366" height="90" usemap="#nav_map"></td>
            <td background="/images/header_right.jpg" align="right" valign="bottom" width="384"><img src="/images/spacer.gif" width="200" height="19" border="0"><br>
            <div align="right"><span style="color: #003333;font-weight: bold;">Logged-In as:</span>&nbsp;<span class="bold"><?=$_SESSION['first_name'].' '.$_SESSION['last_name']?></a>&nbsp;<br>[<a href="/login.php?mode=logout" class="edit">Log-out</a>]&nbsp;</div><br>
            <img src="/images/spacer.gif" width="200" height="9" border="0"><br>
                    <table cellpadding="2" cellspacing="1" border="0">
                        <tr>
                            <td class="search">Paper ID:</td>
                            <td ><input type="text" size="4" name="paper_id" style="width: 40px;height: 18px;"/></td>
                            <td class=""><input type="submit" name="submit" value="Go To Entry" class="submit"/></td>
                        </tr>
                    </table> 
                    </td>
    <? } else { ?>
            <img src="/images/header_logedout.jpg" border="0" width="366" height="90"></td>
            <td><img src="/images/header_right.jpg" border="0" width="384" height="90"></td>
    <? } ?>
                </tr>
            </table></td>
    </tr>
    </form>
    <tr>
        <td class="leftcolumn" width="115" valign="top"><img src="/images/spacer.gif" width="115" height="1"><br>
            <?=vAdmin::drawSubNav($arSubNav,$sNav)?></td>
        <td align="center" width="635" valign="top"></form>