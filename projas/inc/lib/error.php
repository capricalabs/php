<?

/*
 *
 *
 *
 * @author		DWN<dogtowngeek@gmail.com>
 * @copyright	(c) 2005 PROJAS
 * @version		$Id: error.php,v 1.1.1.1 2006-02-08 02:24:33 dan Exp $
 *
 *
 */
 
 class error {
 
    static function printError($oException, $bDie=false){
        $sException = '<pre style="color: red">';
        $sException .=  $oException->__toString() ;
        $sException .= '</pre>';
        
            if($bDie){
                print $sException;
                exit;
            }
        
        return $sException;
    }
    
    static function silentError($oException){
        
        $sError = self::printError($oException);               
        ERROR_DEBUG($sError);
    }
 
 }
 ?>