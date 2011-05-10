<?php

// MultiSelect with Window
// For this to work it requires engineSelectBoxes.js to be included
function emod_msww($attPairs,$engine=null) {
	
	$engine = EngineAPI::singleton();
	
	global $engineVars;
	$dbTables = $engine->dbTablesExport();
	
	$selectors = explode(",",$attPairs['select']);
	$size = (isset($attPairs['size']))?$attPairs['size']:"5";
	
	$whereClause = "";
	if (isset($attPairs['select']) && !is_empty($attPairs['select'])) {
		$whereClause .= "WHERE ";
		$selectArray  = explode(",",$attPairs['select']);
		foreach ($selectArray as $item) {
			$whereClause .= (($whereClause != "WHERE ")?" OR ":"").$attPairs['valuecol']."='".$item."'";
		}
	
	
		$sql = sprintf("SELECT %s as value, %s as label FROM %s %s",
			$engine->openDB->escape($attPairs['valuecol']),
			$engine->openDB->escape($attPairs['labelcol']),
			$engine->openDB->escape($dbTables[$attPairs['table']]["prod"]),
			$whereClause
			);
		$engine->openDB->sanitize = FALSE;
		$sqlResult = $engine->openDB->query($sql);
	}
	//return "SQL: ".$sql."<br />";
	
	$output = '<select id="ms_'.$dbTables[$attPairs['table']]["prod"].'" multiple="multiple" size="'.$size.'" name="ms_'.$dbTables[$attPairs['table']]["prod"].'[]">';
	if (isset($attPairs['select']) && !is_empty($attPairs['select'])) {
		while ($row = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC)) {
			$output .= '<option value="'.htmlsanitize($row['value']).'">'.htmlsanitize($row['label']).'</option>';
		}
	}
	$output .= '</select>';
	$output .= '<input type="button" name="deleteSelected" value="Remove Selected" onclick="emod_msww_removeItemFromID(\'ms_'.$dbTables[$attPairs['table']]["prod"].'\', this.form.ms_'.$dbTables[$attPairs['table']]["prod"].')" />';
	
	$output .= "<br />";
	
	$output .= '<select id="msl_'.$dbTables[$attPairs['table']]["prod"].'" name="msl_'.$dbTables[$attPairs['table']]["prod"].'" class="msww" onchange="emod_msww_addItemToID(\'ms_'.$dbTables[$attPairs['table']]["prod"].'\', this.options[this.selectedIndex])">';
	
	$output .= '<option value="--null--"> -- Select -- </option>';
	
	$sql = sprintf("SELECT %s as value, %s as label FROM %s %s",
		$engine->openDB->escape($attPairs['valuecol']),
		$engine->openDB->escape($attPairs['labelcol']),
		$engine->openDB->escape($dbTables[$attPairs['table']]["prod"]),
		(isset($attPairs['orderby']))?$engine->openDB->escape($attPairs['orderby']):""
		);
	$engine->openDB->sanitize = FALSE;
	$sqlResult = $engine->openDB->query($sql);
	
	while ($row = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC)) {
		$output .= '<option value="'.htmlsanitize($row['value']).'">';
		$output .= htmlsanitize($row['label']);
		$output .= "</option>";
	}
	
	$output .= "</select>";
	
	return($output);
}

function webHelper_listSelect($attPairs,$engine=null) {
	
	$engine = EngineAPI::singleton();
	
	global $engineVars;
	$dbTables = $engine->dbTablesExport();
		
	if (!isset($attPairs['col'])) {
		$attPairs['col'] = "name";
	}
		
	$multiselect = "";
	if (isset($attPairs['type']) && $attPairs['type'] == "multi") {
		$multiselect = 'multiple="multiple"';
	}

	$selected = array();
	if (!empty($attPairs['select'])) {
		$temp = array();
		$selected = explode($engineVars['delim'],$attPairs['select']);
		foreach($selected as $k=>$v) {
			$temp[$v] = $k;
		}
		$selected = $temp;
	}
	
	$output = "<select name=\"".$attPairs['table']."".(!empty($multiselect)?"[]":"")."\" ".$multiselect." id=\"".$attPairs['table']."\">";
	
	if(empty($multiselect)) {
		$output .= "<option value=\"NULL\">-- Select One --</option>";
	}
	
	$sql = sprintf("SELECT * FROM %s ORDER BY %s",
		$engine->openDB->escape($dbTables[$attPairs['table']]["prod"]),
		$engine->openDB->escape($attPairs['col'])
		);
		
	$engine->openDB->sanitize = FALSE;
	$sqlResult = $engine->openDB->query($sql);
	
	if (!$sqlResult['result']) {
		return webHelper_errorMsg("SQL Error".$sqlResult['error']);
	}
	
	while ($row = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC)) {
		$output .= "<option value=\"".htmlsanitize($row['ID'])."\"";
		$output .= (array_key_exists($row['ID'],$selected))?" selected=\"selected\"":"";
		$output .= ">".htmlsanitize($row[$attPairs['col']])."</option>";
	}
	
	$output .= "</select>";
	
	return($output);
}

function webHelper_listCheckbox($attPairs,$engine=null) {
	
	$engine = EngineAPI::singleton();
	
	global $engineVars;
	
	$dbTables = $engine->dbTablesExport();
	
	if (!isset($attPairs['col'])) {
		$attPairs['col'] = "name";
	}
	if (!isset($attPairs['type'])) {
		$attPairs['type'] = "checkbox";
	}
	else {
		$attPairs['type'] = "radio";
	}
	
	$output = "";
	
	$selected = array();
	if (!empty($attPairs['select'])) {
		$temp = array();
		$selected = explode($engineVars['delim'],$attPairs['select']);
		foreach($selected as $k=>$v) {
			$temp[$v] = $k;
		}
		$selected = $temp;
	}
	
	$sql = sprintf("SELECT * FROM %s ORDER BY %s",
		$engine->openDB->escape($dbTables[$attPairs['table']]["prod"]),
		$engine->openDB->escape($attPairs['col'])
		);
		
	$engine->openDB->sanitize = FALSE;
	$sqlResult = $engine->openDB->query($sql);
	
	if (!$sqlResult['result']) {
		return webHelper_errorMsg("SQL Error".$sqlResult['error']);
	}
	

	
	while ($row = mysql_fetch_array($sqlResult['result'], MYSQL_ASSOC)) {
		$output .= "<input type=\"".$attPairs['type']."\" name=\"".$attPairs['table']."[]\" value=\"".$row['ID']."\" ";
		$output .= (array_key_exists($row['ID'],$selected))?" checked=\"checked\"":"";
		$output .= "/>";
		$output .= "<label>".htmlsanitize($row[$attPairs['col']])."</label><br />";
	}
	
	return($output);
}

?>