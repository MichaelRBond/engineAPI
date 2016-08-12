<?php


/**
 * Alias for mysql_real_escape_string()
 * Makes typing a touch easier, plus if we ever plus if we ever want to use something other than mysql_real_escape_string, its easy to switch.
 *
 * @author Michael Bond
 * @see mysql_real_escape_string()
 * @param string $var
 * @return string
 */
function mres($var){
	return mysql_real_escape_string($var);
}

/**
 * Returns a cleaned variable for insertion into mysql
 *
 * @param mixed $var
 * @param bool $quotes
 *        TRUE will wrap each var in single quotes [Default: FALSE]
 * @return array|string
 */
function dbSanitize($var, $quotes = FALSE) {
	//run each array item through this function (by reference)
    if (is_array($var)) {
        foreach ($var as $I => $val) {
            $var[$I] = dbSanitize($val);
        }
    }
	//clean strings
    else if (is_string($var)) {
        $var = mres($var);
        if ($quotes) {
            $var = "'". $var ."'";
        }
    }
	//convert null variables to SQL NULL
    else if (isnull($var)) {
        $var = "NULL";
    }
	//convert boolean variables to binary boolean
    else if (is_bool($var)) {
        $var = ($var) ? 1 : 0;
    }
    return $var;
}

/**
 * Sanitize untrusted input for safe HTML output.
 * Uses php htmlentities function, If provided an array, it will sanitize each member of the array. Arrays of arrays are supported.
 *
 * @see htmlentities()
 * @param string|array $var
 * @param int $flags
 *        Bitwise flags for htmlentities() call
 * @param string $charSet
 * @param bool $doubleEncode
 *        True to encode existing HTML entities [Default: TRUE]
 * @return array|bool|string
 */
function htmlSanitize($var, $flags=ENT_QUOTES, $charSet="UTF-8", $doubleEncode=TRUE) {
	if(!isset($var)) return(FALSE);

	//run each array item through this function (by reference)
    if (is_array($var)) {
        foreach ($var as $I => $val) {
            $var[$I] = htmlSanitize($val);
        }
    }
	else {
		$var = htmlentities($var, $flags, $charSet, $doubleEncode);
	}

	return($var);
}

/**
 * Sanitize json data structures for either HTML or MYSQL usage.
 *
 * @param array $var
 *        Array from php function json_decode
 * @param string $type
 *        HTML or MYSQL [Default: mysql]
 * @return array|bool|string
 */
function jsonSanitize($var,$type="mysql") {

	$type = strtolower($type);

	if ($type != "mysql" && $type != "html") {
		return($type);
	}

	switch($type) {
		case "mysql":
			$sanitizeFunction = "dbSanitize";
			break;
		case "html":
			$sanitizeFunction = "htmlSanitize";
			break;
		default:
			return(FALSE);
			break;
	}

	$varSanitized = array();

	foreach((array)$var as $I=>$V) {
		$index = $sanitizeFunction($I);

		if (is_array($V)) {
			$value = jsonSanitize($V,$type);
		}
		else {
			$value = $sanitizeFunction($V);
		}

		$varSanitized[$index] = $value;

	}

	return($varSanitized);
}

/**
 * remove \r from a string if the $engineVar 'stripCarriageReturns' is TRUE
 * If you modify that variable to use this functions its best to return it to
 * its original state
 *
 * @param string $string
 * @return string
 */
function stripCarriageReturns($string) {

	global $engineVars;

	if ($engineVars['stripCarriageReturns'] === TRUE) {
		$string = str_replace("\r","",$string);
	}

	return($string);
}

/**
 * remove \r and \n from a string
 *
 * @param string $string
 * @return string
 */
function stripNewLines($string) {
	$string = str_replace("\r","",$string);
	$string = str_replace("\n","",$string);
	return($string);
}


?>