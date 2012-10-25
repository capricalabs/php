<? 
/*
 *
 *
 *
 * @author		DWN<dogtowngeek@gmail.com>
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: cMasterController.php,v 1.2 2006-03-02 21:54:31 dan Exp $
 *
 * @notes;      these properties are shared with every controller
 *
 */

class cMasterController{

    /*-- page construction info --*/
   
    public $success_message;
    public $error_message;
    public $message;
    public $pgtitle;
    public $content;
    public $formInclude;
    public $topPaging;
    public $bottomPaging;
    public $template = 'common.tmpl';
    public $form_action;
    
    /*-- applicaton direction --*/
    
    public $action;
    public $submode;
    
    /*-- object holders --*/
    
    public $oTemp;
    public $oEmail;
    public $oReport;
}
?>