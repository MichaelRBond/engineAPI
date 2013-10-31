<?php 

class config {
	
	private static $classInstance;

	public function __construct() {

		//@TODO this isn't DRY. Needs refactoring

		// setup private config variables 
		// require $engineDir."/config/defaultPrivate.php";
		// if($site != "default" && $site != "defaultPrivate"){
		// 	$siteConfigFile = $engineDir."/config/".$site."Private.php";
		// 	require_once $siteConfigFile;
		// }
		// $this->variables['private'] = $engineVarsPrivate;
		// unset($engineVarsPrivate);

		// // setup $engineVars
		// require $engineDir."/config/default.php";
		// if($site != "default" && $site != "defaultPrivate"){
		// 	$siteConfigFile = $engineDir."/config/".$site.".php";
		// 	require_once $siteConfigFile;
		// }
		// $this->variables['engine'] = $engineVars;
		// unset($engineVars);

		// $this->variables['local'] = array();

		// $this->set("engine","engineDir",$engineDir);

	}

	public function loadConfig($file) {

		$varsBefore = array_keys(get_defined_vars());
		require $file;
		$varsAfter  = array_keys(get_defined_vars());
		return compact(array_diff($varsAfter, $varsBefore));

		// require $file;
		// if($site != "default" && $site != "defaultPrivate"){
		// 	$siteConfigFile = $engineDir."/config/".$site."Private.php";
		// 	require_once $siteConfigFile;
		// }
		// $this->variables['private'] = $engineVarsPrivate;
		// unset($engineVarsPrivate);
	}

	public static function getInstance($engineDir=NULL,$site="default") {

		return new self();
	}

	public function is_set($name) {

		if (isset($this->variables[$name])) return TRUE;

		return FALSE;

	}

	public function set($name,$value,$null=FALSE) {

		if (is_array($name) === TRUE && count($name) > 1) {
			$arrayLen = count($name);
			$count    = 0;

			foreach ( $name as $V ) { 
				$count++;
				if ($count == 1) { 
					$this->variables[$V] = array(); 
					$prevTemp = &$this->variables[$V]; 
				} 
				else { 
					if ($count == $arrayLen) {
                        // $prevTemp[$V] = $value;
						if ($prevTemp[$V] = $value) {
							return TRUE;
						}
					}
					else {
						$prevTemp[$V] = array(); 
						$prevTemp = &$prevTemp[$V]; 
					}
				} 
			}

			return TRUE;
		}

		if (isnull($value) && $null === TRUE) {
			$this->variables[$name] = "%eapi%1ee6ba19c95e25f677e7963c6ce293b4%api%";
			return TRUE;
		}
		
		if(isset($value)) {
			$this->variables[$name] = $value;
			return TRUE;
		}
		
		return FALSE;

	}

	public function get($name,$default="") {

		// @TODO private ACLs need to be put into place
		// @TODO should only return a type if it is called from self:: or from the correct 
		// 		 class

		if (is_array($name)) {
			$arrayLen = count($name);
			$count    = 0;

			foreach ( $name as $V ) { 
				$count++;
				if ($count == 1) { 
					if (isset($this->variables[$V])) {
						$prevTemp = &$this->variables[$V];
					} 
					else {
						return(NULL);
					}

				} 
				else { 
					if (!isset($prevTemp[$V])) {
						return(NULL);
					}
					else {
						$prevTemp = &$prevTemp[$V]; 
					}
					if ($count == $arrayLen) {
						return($prevTemp);
					}
				} 
			}
			return $default;
		}

		if (array_key_exists($name,$this->variables)) {
			if ($this->variables[$name] == "%eapi%1ee6ba19c95e25f677e7963c6ce293b4%api%") {
				return NULL;
			}
			return $this->variables[$name];
		}
		
		return $default;

	}

	public function remove($name) {
		
		if (array_key_exists($name,$this->variables)) {
			unset($this->variables[$name]);
			return TRUE;
		}
		
		return FALSE ;
		
	}

	public function variable($name,$value=NULL,$null=FALSE) {
		if (isnull($value) && $null === FALSE) {
			return $this->get($name);
		}
		
		return $this->set($name,$value,$null);
	}

	public function export() {
		return $this->variables;
	}

}

?>