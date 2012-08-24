<?php

class eapi_session {
	
	//Template Stuff
	// private $pattern = "/\{eapi_include\s+(.+?)\}/";
	// private $function = "eapi_includes::templateMatches";
	
	function __construct() {		
		// EngineAPI::defTempPatterns($this->pattern,$this->function,$this);
		// EngineAPI::defTempPatterns("/\{engine name=\"include\"\s+(.+?)\}/",$this->function,$this);
		
		EngineAPI::defTempPatterns("/\{csrf\s+(.+?)\}/","eapi_session::csrf",$this);
		
		EngineAPI::defTempPatterns("/\{engine name=\"csrf\"\s*(.*?)\}/","eapi_session::csrf",$this);
		EngineAPI::defTempPatterns("/\{engine name=\"insertCSRF\"\s*(.*?)\}/","eapi_session::csrf",$this);
		
		EngineAPI::defTempPatterns("/\{engine name=\"csrfGet\"\s*(.*?)\}/","eapi_session::csrfGet",$this);
		
		EngineAPI::defTempPatterns("/\{engine name=\"session\"\s+(.+?)\}/","eapi_session::sessionGet",$this);
	}
	
	public static function sessionGet($matches) {
		$engine        = EngineAPI::singleton();
		$eapi_function = $engine->retTempObj("eapi_function");
		$attPairs      = attPairs($matches[1]);
		
		$output = sessionGet($attPairs['var']);
		return($output);
	}
	
	public static function csrf($matches) {
		
		$engine        = EngineAPI::singleton();
		$eapi_session  = $engine->retTempObj("eapi_session");
		$attPairs      = attPairs($matches[1]);
		
		if (isset($attPairs['insert']) && strtolower($attPairs['insert']) != "post") {
			$output = sessionInsertCSRF(FALSE);
		}
		else {
			$output = sessionInsertCSRF();
		}
		
		return($output);
	}
	
	public static function csrfGet($matches) {
		$output = sessionInsertCSRF(FALSE);
		return($output);
	}
	
	public static function templateMatches($matches) {
		$engine        = EngineAPI::singleton();
		$eapi_function = $engine->retTempObj("eapi_function");
		$attPairs      = attPairs($matches[1]);
		
		if (!isset($attPairs['files']) && isempty($attPairs['type'])) {
			return(FALSE);
		}
		
		$output = recurseInsert($attPairs['file'],$attPairs['type']);
		
		return($output);
	}
	
}

?>