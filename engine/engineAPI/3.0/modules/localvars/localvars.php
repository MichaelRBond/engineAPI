<?php

class localvars {

	private static $localvars = array();
	
	function __construct() {		

		EngineAPI::defTempPatterns("/\{local\s+(.+?)\}/","localvars::templateMatches",$this);
	}
	
	function __destruct() {
	}
	
	public static function templateMatches($matches) {
		
		$engine        = EngineAPI::singleton();
		$eapi_function = $engine->retTempObj("eapi_function");
		$attPairs      = attPairs($matches[1]);
		
		if (isset(self::$localvars[$attPairs['var']]) && !is_empty(self::$localvars[$attPairs['var']])) {
			return(self::$localvars[$attPairs['var']]);
		}

		return("");
		
	}
	
	public static function add($var,$value,$null=FALSE) {
		
		if (isnull($value) && $null === TRUE) {
			self::$localvars[$var] = "%eapi%1ee6ba19c95e25f677e7963c6ce293b4%api%";
			return(TRUE);
		}
		
		if(isset($value)) {
			self::$localvars[$var] = $value;
			return(TRUE);
		}
		
		return(FALSE);
		
	}
	
	public static function get($var,$default="") {
		
		if (array_key_exists($var,self::$localvars)) {
			if (self::$localvars[$var] == "%eapi%1ee6ba19c95e25f677e7963c6ce293b4%api%") {
				return(NULL);
			}
			return(self::$localvars[$var]);
		}
		
		return($default);
		
	}
	
	public static function del($var) {
		
		if (array_key_exists($var,self::$localvars)) {
			unset(self::$localvars[$var]);
			return(TRUE);
		}
		
		return(FALSE);
		
	}
	
	public static function variable($var,$value=NULL,$null=FALSE) {
		
		if (isnull($value) && $null === FALSE) {
			return self::get($var);
		}
		
		return self::add($var,$value,$null);
		
	}
	
	public static function export() {
		return self::$localvars;
	}

    /**
     * This will import a key->value database table into local vars (very useful for a 'settings' table)
     * @static
     * @param $tblName      - The database table
     * @param $nameField    - The table field holding the setting name
     * @param $valueField   - The table field holding the setting value
     * @param array $params - Optional array of additional params
     *          + dbConn    - The database connection to use (default: EngineAPI::openDB)
     *          + namespace - A namespace to put the imported settings into (default: '')
     *          + sqlWhere  - SQL Where clause to use for sql statement
     * @return bool|int     - Returns number of localVars created, or bool FALSE on error
     */
    public static function dbImport($tblName, $nameField, $valueField, $params=array()){
        // Handle default params
        $params = array_merge(array(
            'dbConn'    => EngineAPI::singleton()->openDB,
            'namespace' => '',
            'sqlWhere'  => '1=1'
        ), (array)$params);

        // Run SQL
        $dbSettings = $params['dbConn']->query(sprintf("SELECT `%s`,`%s` FROM `%s` WHERE %s",
            $params['dbConn']->escape($nameField),
            $params['dbConn']->escape($valueField),
            $params['dbConn']->escape($tblName),
            $params['dbConn']->escape($params['sqlWhere'])));
        if($dbSettings['result']){
            $settingCount = 0;
            while($row = mysql_fetch_assoc($dbSettings['result'])){
                self::add($params['namespace'].$row[ $nameField ], $row[ $valueField ]);
                $settingCount++;
            }
            return $settingCount;
        }else{
            errorHandle::newError(sprintf(__METHOD__."() - SQL Error [%s:%s]", $dbSettings['errorNumber'], $dbSettings['error']), errorHandle::DEBUG);
            return FALSE;
        }
    }
}

?>