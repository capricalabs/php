<?php
/*
 *
 *
 *
 * @author		DWN<dogtowngeek@gmail.com>
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: bot.inc,v 1.8 2006-03-15 02:48:02 dan Exp $
 *
 */
?>
        <br><br></td>
    </tr>
    <tr>
        <td height="100%" class="leftcolumn"></td>
        <td>&nbsp;</td>
    </tr>
</table>
<span class="small">&copy; Copyright 1993-2006 AI Access Foundation, Inc.</span><br/>
<?
$myDB->closeConnection();
DEBUG_PRINT();
?>
</body>
    <map name="nav_map">
    <? if(cLogin::hasPermission()) {?>
        <area shape="rect" alt="Other" coords="301,68,357,86" href="/other.php">
        <area shape="rect" alt="Reports" coords="226,68,285,86" href="/reports.php">
        <area shape="rect" alt="People" coords="152,68,210,86" href="/people.php">
        <area shape="rect" alt="Publish Papers" coords="80,68,135,86" href="/publish.php">
        <area shape="rect" alt="Review Papers" coords="11,68,67,86" href="/paper.php">
    <? } else { ?>
        <area shape="rect" alt="Log-out" coords="226,68,286,86" href="/logout.php">
        <area shape="rect" alt="Reports" coords="151,68,211,86" href="/reports.php">
        <area shape="rect" alt="People" coords="79,68,135,86" href="/people.php">
        <area shape="rect" alt="Review Papers" coords="10,68,67,86" href="/paper.php">
    <? } ?>
    </map>
</html>