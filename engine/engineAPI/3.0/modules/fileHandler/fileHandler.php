<?php

class fileHandler {
	
	private $engine = NULL;
	private $file = array();
	private $allowedExtensions = array();
	
	public $maxSize = 2000000; // 2mb
	public $basePath = NULL;
	
	
	function __construct() {
		
		$this->engine = EngineAPI::singleton();
		
	}
	
	function __destruct() {
	}
	
	public function validate($files,$i) {
		
		if (empty($files['name'][$i])) {
			return webHelper_errorMsg("File skipped: No File Name");
		}
		else {
			
			$fileSize = dbSanitize($files['size'][$i]);
			$fileName = dbSanitize($files['name'][$i]);
			
			// file not uploaded correctly, display PHP error
			if ($files['error'][$i] == 1) {
				return webHelper_errorMsg("File skipped: ".$fileName." exceeds the maximum file size set in PHP.");
			}
			
			if ($fileSize > $this->maxSize) {
				return webHelper_errorMsg("File skipped: ".$fileName." (".$this->displayFileSize($fileSize).") exceeds size limit of ".$this->displayFileSize($this->maxSize).".");
			}
			
			if (($output = $this->checkAllowedExtensions($fileName)) !== TRUE) {
				return webHelper_errorMsg("File skipped: ".$output);
			}
			
		}
		
		return TRUE;
		
	}
	
	public function retrieve($type,$name,$location,$fields=NULL) {
		
		switch($type) {
			case "database":
				return $this->retrieveFromDB($name,$location,$fields);
				
			case "folder":
				return $this->retrieveFromFolder($name,$location);
				
			default:
				return FALSE;
		}
		
	}
	
	public function store($type,$files,$location,$fields=NULL) {
		
		$files = $this->normalizeArrayFormat($files);
		
		switch ($type) {
			case "database":
				return $this->storeInDB($files,$location,$fields);
				
			case "folder":
				return $this->storeInFolder($files,$location);
				
			default:
				return FALSE;
		}
		
	}
	
	private function normalizeArrayFormat($files) {
		
		if (!is_array($files['name'])) {
			$tmp = array();
			foreach ($files as $key => $val) {
				$tmp[$key][0] = $val;
			}
			$files = $tmp;
		}
		
		return $files;
		
	}
	
	private function retrieveFromFolder($name,$location) {
		
		$filePath = $this->basePath."/".$location.'/'.$name;
		
		if (!file_exists($filePath)) {
			return FALSE;
		}
		
		$content = file_get_contents($filePath);
		$type = $this->getMimeType($filePath);
		
		$output['name'] = utf8_encode($name);
		$output['type'] = $type;
		$output['data'] = $content;
		
		return $output;
		
	}
	
	private function retrieveFromDB($name,$table,$fields) {
		
		$select = "";
		foreach ($fields AS $val) {
			$select .= (empty($select)?"":", ").$val;
		}
		
		$sql = sprintf("SELECT %s FROM %s WHERE %s='%s' LIMIT 1",
			$select,
			$this->engine->openDB->escape($this->engine->dbTables($table)),
			$fields['name'],
			$name
			);
		$engine->openDB->sanitize = FALSE;
		$sqlResult                = $engine->openDB->query($sql);
		
		if ($sqlResult['affectedRows'] == 0) {
			return FALSE;
		}
		
		$file = mysql_fetch_assoc($sqlResult['result']);
		
		$output['name'] = $file[$fields['name']];
		$output['type'] = $file[$fields['type']];
		$output['data'] = $file[$fields['data']];
		
		return $output;
		
	}
	
	private function storeInDB($files,$table,$fields) {
		
		$errorMsg  = NULL;
		$fileCount = count($files['name']);
		
		for ($i = 0; $i < $fileCount; $i++) {
			
			if (($valid = $this->validate($files,$i)) !== TRUE) {
				$errorMsg .= $valid;
				continue;
			}
			
			$fileName = $files['name'][$i];
			$fileType = $files['type'][$i];
			$fileData = file_get_contents($files['tmp_name'][$i]);
			
			$sql = sprintf("INSERT INTO %s SET %s='%s', %s='%s', %s='%s'",
				$this->engine->openDB->escape($this->engine->dbTables($table)),
				$this->engine->openDB->escape($fields['name']),
				$this->engine->openDB->escape($files['name'][$i]),
				$this->engine->openDB->escape($fields['data']),
				$this->engine->openDB->escape(file_get_contents($files['tmp_name'][$i])),
				$this->engine->openDB->escape($fields['type']),
				$this->engine->openDB->escape($fileType)
				);
			$this->engine->openDB->sanitize = FALSE;
			$sqlResult                      = $this->engine->openDB->query($sql);
			
			if (!$sqlResult['result']) {
				$errorMsg .= webHelper_errorMsg("Failed to upload ".$fileName);
			}
			
		}
		
		if (!isnull($errorMsg)) {
			return $errorMsg;
		}
		else {
			return TRUE;
		}
		
	}
	
	private function storeInFolder($files,$folder) {
		
		$errorMsg = NULL;
		
		$location = $this->basePath."/".$folder;
		if (!is_dir($location)) {
			mkdir($location, 0700, TRUE);
		}

		$fileCount = count($files['name']);
		
		for ($i = 0; $i < $fileCount; $i++) {
			
			if (($valid = $this->validate($files,$i)) !== TRUE) {
				$errorMsg .= $valid;
				continue;
			}
			
			$fileName = utf8_decode($files['name'][$i]);
			$fileType = $files['type'][$i];
			$fileData = $files['tmp_name'][$i];
			
			if (file_exists($location."/".$fileName)) {
				$errorMsg .= webHelper_errorMsg("Conflicting filename: ".$fileName);
			}
			else {
				if (is_uploaded_file($fileData)) {
					$output = move_uploaded_file($fileData,$location."/".$fileName);
				}
				else {
					$output = $this->copyFile($fileName,$location."/".$fileName);
				}
				
				if ($output === FALSE) {
					$errorMsg .= webHelper_errorMsg("Error storing ".$fileName);
				}
			}
			
		}
		
		if (!isnull($errorMsg)) {
			return $errorMsg;
		}
		else {
			return TRUE;
		}
		
	}
	
	public function uploadForm($name,$multiple=FALSE,$hiddenFields=NULL) {
		
		$output = NULL;
		
		$output .= "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']."&amp;uploadID=".(sessionGet("fileUploadID"))."\" enctype=\"multipart/form-data\">";
		$output .= "<input type=\"file\" name=\"".$name."[]\" id=\"".$name."_fileInsert\" ".(($multiple)?"multiple":"")." />";
		
		if (!isnull($hiddenFields)) {
			foreach($hiddenFields as $I=>$V) {
				$output .= "<input type=\"hidden\" name=\"".$V['field']."\" value=\"".$V['value']."\" />";
			}
		}
		
		$output .= sessionInsertCSRF();
		$output .= "<input type=\"submit\" name=\"".$name."_submit\" value=\"Upload\" />";
		$output .= "</form>";
		
		return $output;
	}
	
	public function dbInsert($table,$fields) {
		
		$sqlStr = NULL;
		
		foreach ($fields as $val) {
			if(!empty($val['field'])) {
				
				$sqlStr .= (isnull($sqlStr)?"":", ").dbSanitize($val['field'])." = '".dbSanitize($val['value'])."'";
				
			}
		}
		
		$sql = sprintf("INSERT INTO %s SET %s",
			$this->engine->openDB->escape($this->engine->dbTables($table)),
			$sqlStr
			);
		$this->engine->openDB->sanitize = FALSE;
		$sqlResult                      = $this->engine->openDB->query($sql);
		
		if (!$sqlResult['result']) {
			return webHelper_errorMsg("Insert Error: ");
		}
		
		return TRUE;		
		
	}
	
	public function copyDbRecord($oldTable,$newTable,$fields,$mysqlFileName=TRUE) {
		
		// Get fields from Old Table
		$oldTableFields = array();
		
		$sql = sprintf("SHOW FIELDS FROM $oldTable");
		$this->engine->openDB->sanitize = FALSE;
		$sqlResult                = $this->engine->openDB->query($sql);
		while($row = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC)) {
			$oldTableFields[$row['Field']] = TRUE;
		}
		
		// Get fields from New Table
		$newTableFields = array();
		
		$sql = sprintf("SHOW FIELDS FROM $newTable");
		$this->engine->openDB->sanitize = FALSE;
		$sqlResult                = $this->engine->openDB->query($sql);
		while($row = mysql_fetch_array($sqlResult['result'],  MYSQL_ASSOC)) {
			$newTableFields[$row['Field']] = TRUE;
		}
		
		
		// Grab data from oldTable from the database
		$sql = sprintf("SELECT * FROM %s WHERE %s = '%s'",
			$this->engine->openDB->escape($this->engine->dbTables($oldTable)),
			$this->engine->openDB->escape($fields['id']['field']),
			$this->engine->openDB->escape($fields['id']['value'])
			);
		$this->engine->openDB->sanitize = FALSE;
		$sqlResult = $this->engine->openDB->query($sql);
		
		$row = mysql_fetch_assoc($sqlResult['result']);
		
		// save and nullify old ID
		$oldID = $row[$fields['id']['field']];
		$row[$fields['id']['field']] = NULL;
		
		// Remove the ID, so we aren't copying it over
		unset($oldTableFields[$fields['id']['field']]);
		
		// Get rid of fields that are in oldTable but NOT in newTable
		$nullFields = array_diff_key($oldTableFields,$newTableFields);
		foreach ($nullFields as $I=>$V) {
			unset($oldTableFields[$I]);
		}
		
		// Build the list of fields we are inserting
		$insertFieldNames = "(".(implode(",",array_keys($oldTableFields))).")";
		
		// Grab the values from the oldTable that will be inserted in the newTable
		foreach (array_keys($oldTableFields) as $I) {
			$vals[] = "'".dbSanitize($row[$I])."'";
		}
		$insertFieldVals = implode(",",$vals);
			
		// insert record into new table
		$sql = sprintf("INSERT INTO %s %s VALUES(%s)",
			$this->engine->openDB->escape($this->engine->dbTables($newTable)),
			$insertFieldNames,
			$insertFieldVals
			);
		$this->engine->openDB->sanitize = FALSE;
		
		$sqlResult = $this->engine->openDB->query($sql);

		if (!$sqlResult['result']) {
			return(FALSE);
		}
		
		$return       = array();
		$return['id'] = $sqlResult['id'];
		
		// delete record from old table
		$sql = sprintf("DELETE FROM %s WHERE %s = '%s' LIMIT 1",
			$this->engine->openDB->escape($this->engine->dbTables($oldTable)),
			$this->engine->openDB->escape($fields['id']['field']),
			$oldID
			);
		$this->engine->openDB->sanitize = FALSE;
		$sqlResult = $this->engine->openDB->query($sql);

		// generate new filename based on $newID
		if ($mysqlFileName === TRUE) {
			
			$output = $this->genMysqlFileName($newTable,$fields,$return['id']);
			
			if ($output === FALSE) {
				return webHelper_errorMsg("Update Error.");
			}
			
			$return['fileName'] = $output['fileName'];
			$return['paddedID'] = $output['paddedID'];
			$return['dir']      = $output['dir'];
			
			$return["oldFileName"] = $row['directory']."/".$row['fileName'];
			$return["newFileName"] = $output['dir']."/".$output['fileName'];
			
		}
		
		return $return;
		
	}
	
	public function genMysqlFileName($table,$fields,$ID,$updateDB=TRUE) {
		
		$paddedID = str_pad($ID, 3, "0", STR_PAD_LEFT);
		$ext = pathinfo($fields['name']['value'], PATHINFO_EXTENSION );
		$dir = $this->basePath."/".substr($paddedID,"0","1")."/".substr($paddedID,"1","1")."/".substr($paddedID,"2","1");
		
		$newName = $paddedID.".".$ext;
		
		if ($updateDB === TRUE) {
			$sql = sprintf("UPDATE %s SET %s = '%s', %s = '%s' WHERE %s = '%s'",
				$this->engine->openDB->escape($this->engine->dbTables($table)),
				$this->engine->openDB->escape($fields['name']['field']),
				$this->engine->openDB->escape($newName),
				$this->engine->openDB->escape($fields['dir']['field']),
				$this->engine->openDB->escape($dir),
				$this->engine->openDB->escape($fields['id']['field']),
				$this->engine->openDB->escape($ID)
				);
			$this->engine->openDB->sanitize = FALSE;
			$sqlResult                      = $this->engine->openDB->query($sql);
		
			if (!$sqlResult['result']) {
				return FALSE;
			}
		}
		
		$return = array();
		$return['fileName'] = $newName;
		$return['paddedID'] = $paddedID;
		$return['dir']      = $dir;
		
		return $return;
		
	}
	
	public function moveFile($oldFileName,$newFileName) {
		
		if (!file_exists($oldFileName)) {
			return webHelper_errorMsg("Error moving file: $newFileName source not found");
		}
		if (file_exists($newFileName)) {
			return webHelper_errorMsg("Error moving file: Conflicting filename in target directory");
		}
		
		return rename($oldFileName,$newFileName);
		
	}
	
	public function copyFile($sourceFile,$destFile) {
		if (!file_exists($sourceFile)) {
			return webHelper_errorMsg("Error copying file: $sourceFile source not found");
		}
		if (file_exists($destFile)) {
			return webHelper_errorMsg("Error copying file: Conflicting filename in target directory");
		}
		
		return copy($sourceFile,$destFile);
	}
	
	public function deleteFile($fileName) {
		if (file_exists($fileName)) {
			return unlink($fileName);
		}
		return webHelper_errorMsg("Error deleting file: $fileName");
	}
	
	public function displayFile($file,$display=NULL) {
		
		global $engineVars;
		
		if (isnull($display)) {
			$display = "window";
		}
		
		sessionSet("FMfileName",$file['name']);
		sessionSet("FMfileType",$file['type']);
		sessionSet("FMfileData",$file['data']);
		sessionSet("FMdisplay",$display);
		
		switch ($display) {
			case "inline":
				return $this->displayFileInline($file);
				break;
			case "download":
			case "window":
			default:
				header("Location: " . $engineVars['downloadPage']);
		}
		
	}
	
	private function displayFileInline($file) {
		
		global $engineVars;
		
		if (strpos($file['type'],'image') !== FALSE) {
			$output = "<img src=\"".$engineVars['downloadPage']."\" />";
		}
		else {
			$output = $file['data'];
		}
		
		return $output;
		
	}

	public function displayFileSize($filesize){
		
		if (is_numeric($filesize)) {
			$decr = 1000;
			$step = 0;
			$prefix = array('Byte','KB','MB','GB','TB','PB');
			
			while (($filesize / $decr) > 0.9) {
				$filesize = $filesize / $decr;
				$step++;
			}
			
			return round($filesize,2).' '.$prefix[$step];
		}
		else {
			return 'NaN';
		}
	}
	
	public function displaySearchForm($extentions=array()) {
		
		if (is_empty($extentions)) {
			$extentions = $fh->getExtensionsInFolder();
			natcasesort($extentions);
		}
		
		$limits = array(1=>"Bytes", 1000=>"KB", 1000000=>"MB", 1000000000=>"GB");
		
		$lowSizeUnit  = is_empty($this->engine->cleanPost['MYSQL']['lowSizeUnit'])?1000:$this->engine->cleanPost['MYSQL']['lowSizeUnit'];
		$highSizeUnit = is_empty($this->engine->cleanPost['MYSQL']['highSizeUnit'])?1000:$this->engine->cleanPost['MYSQL']['highSizeUnit'];
		
		$output = '<form action="'.$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'" method="post">';
		$output .= '<table>';
		$output .= '<tr>';
		$output .= '<td>File Name</td>';
		$output .= '<td><input type="text" name="fileName" value="'.$this->engine->cleanPost['MYSQL']['fileName'].'" /></td>';
		$output .= '</tr>';
		
		$output .= '<tr>';
		$output .= '<td>Type</td>';
		$output .= '<td>';
		$output .= '<select name="fileType">';
		$output .= '<option value="any">Any</option>';
		foreach ($extentions as $ext) {
			$output .= '<option value="'.$ext.'" '.(($ext==$this->engine->cleanPost['MYSQL']['fileType'])?'selected':'').'>'.$ext.'</option>';
		}
		$output .= '</select>';
		$output .= '</td>';
		$output .= '</tr>';

		$output .= '<tr>';
		$output .= '<td>Size Limit</td>';
		$output .= '<td>';
		$output .= '<input type="text" name="lowSizeLimit" value="'.$this->engine->cleanPost['MYSQL']['lowSizeLimit'].'" size="10" />';
		$output .= '<select name="lowSizeUnit">';
		foreach ($limits as $k => $v) {
			$output .= '<option value="'.$k.'" '.(($k==$lowSizeUnit)?'selected':'').'>'.$v.'</option>';
		}
		$output .= '</select>';
		$output .= 'To';
		$output .= '<input type="text" name="highSizeLimit" value="'.$this->engine->cleanPost['MYSQL']['highSizeLimit'].'" size="10" />';
		$output .= '<select name="highSizeUnit">';
		foreach ($limits as $k => $v) {
			$output .= '<option value="'.$k.'" '.(($k==$highSizeUnit)?'selected':'').'>'.$v.'</option>';
		}
		$output .= '</select>';
		$output .= '</td>';
		$output .= '</tr>';
		
		$output .= '<tr>';
		$output .= '<td colspan="2">';
		$output .= '{engine name="insertCSRF"}';
		$output .= '<input type="submit" name="fileSubmit" value="Submit" />';
		$output .= '</td>';
		$output .= '</tr>';
		$output .= '</table>';
		$output .= '</form>';

		return $output;

	}

	public function search($attPairs, $folder=NULL) {
		
		$results = array();
		$dir     = $this->basePath."/".$folder;
		$files   = scandir($dir);

		foreach ($files as $file) {
			
			// ignore .files
			if ($file[0] == '.') {
				continue;
			}

			if (is_dir($dir."/".$file)) {
				// if it's a directory, recurse into it
				$results = array_merge($results, $this->search($attPairs,$folder."/".$file));
			}
			else if (!is_dir($dir."/".$file)) {
				
				// physical file properties
				$tmp         = array();
				$tmp['name'] = $file;
				$tmp['path'] = $dir."/".$file;
				$tmp['size'] = filesize($dir."/".$file);
				$tmp['type'] = pathinfo($file, PATHINFO_EXTENSION);

				// if lookup is defined, use the lookup value instead of the file value
				// used when a value is stored in the database instead
				if (isset($attPairs['lookup']) && !is_empty($attPairs['lookup'])) {
					foreach ($attPairs['lookup'] as $key => $value) {
						$sql = sprintf("SELECT %s FROM %s.%s WHERE %s='%s' LIMIT 1",
							$this->engine->openDB->escape($value['field']),
							$this->engine->openDB->escape($value['database']),
							$this->engine->openDB->escape($value['table']),
							$this->engine->openDB->escape($value['matchOn']),
							$this->engine->openDB->escape($file)
							);
						$this->engine->openDB->sanitize = FALSE;
						$sqlResult                      = $this->engine->openDB->query($sql);
						
						if ($sqlResult['affectedRows'] == 0) {
							continue(2);
						}

						$row = mysql_fetch_array($sqlResult['result'], MYSQL_NUM);
						$tmp[$key] = $row[0];
					}

				}

				// skip file if searched string is not contained in file name
				if (isset($attPairs['name']) && !is_empty($attPairs['name'])) {
					if (strpos(strtolower($tmp['name']), strtolower($attPairs['name'])) === FALSE) {
						continue;
					}
				}

				// skip file if searched type is different
				if (isset($attPairs['type']) && !is_empty($attPairs['type'])) {
					if (strtolower($attPairs['type']) != strtolower($tmp['type'])) {
						continue;
					}
				}

				// skip file if file size is smaller than the searched low limit
				if (isset($attPairs['size']['low']) && !is_empty($attPairs['size']['low']) && $attPairs['size']['low'] != 0) {
					if ($tmp['size'] < $attPairs['size']['low']) {
						continue;
					}
				}

				// skip file if file size is larger than the searched high limit
				if (isset($attPairs['size']['high']) && !is_empty($attPairs['size']['high']) && $attPairs['size']['high'] != 0) {
					if ($tmp['size'] > $attPairs['size']['high']) {
						continue;
					}
				}

				$results[] = $tmp;

			}

		}

		return $results;

	}

	public function getExtensionsInFolder($folder=NULL) {
		
		$extArr = array();
		$dir     = $this->basePath."/".$folder;
		$files  = scandir($dir);

		foreach ($files as $file) {
			
			if ($file[0] == '.') {
				continue;
			}

			if (is_dir($dir."/".$file)) {
				$extArr = array_unique(array_merge($extArr, $this->getExtensionsInFolder($folder."/".$file)));
			}
			else if (!is_dir($dir."/".$file)) {
				$extArr[] = strtolower(pathinfo($file, PATHINFO_EXTENSION));
			}

		}

		return $extArr;

	}
	
	public function addAllowedExtension($extension) {
		
		if (!in_array($extension,$this->allowedExtensions)) {
			$this->allowedExtensions[] = $extension;
		}
		
		return TRUE;
		
	}
	
	private function checkAllowedExtensions($fileName) {
		
		$fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
		
		if (!in_array($fileExt,$this->allowedExtensions)) {
			return ($fileName.": Invalid file type \"".$fileExt."\"");
		}
		
		return TRUE;
		
	}
	
	public function getMimeType($file_path) {
		global $engineVars;
		$mimeType = '';
		
		try{
			if(!class_exists('finfo')) throw new Exception("finfo class unavailable!");
			$fileInfo = @finfo_open(FILEINFO_MIME);
			if(!$fileInfo and isset($engineVars['magicMimeFile'])) $fileInfo = finfo_open(FILEINFO_MIME, $engineVars['magicMimeFile']);
			if(is_object($fileInfo)) $mimeType = $finfo->file($file_path);
			else throw new Exception("Unable to open FileInfo database!");
		}catch(Exception $e){		
			try{
				if(!function_exists('mime_content_type')) throw new Exception("mime_content_type() unavailable!");
				$mimeType = mime_content_type($file_path);
			}catch(Exception $e){
				$mimeType = $this->returnMIMEType($file_path);
			}
		}
		
		// Is this needed?
		if($mimeType == '') $mimeType = "application/force-download";
		
		return $mimeType;		
	}
	
	private function returnMIMEType($filename) {
		
		preg_match("|\.([a-z0-9]{2,4})$|i", $filename, $fileSuffix);
		
		if (!isset($fileSuffix[1])) {
			return "unknown";
		}

		switch (strtolower($fileSuffix[1])) {
			case "js":
				return "application/x-javascript";
				
			case "json":
				return "application/json";
				
			case "jpg":
			case "jpeg":
			case "jpe":
				return "image/jpg";
				
			case "png":
			case "gif":
			case "bmp":
			case "tiff":
				return "image/".strtolower($fileSuffix[1]);
				
			case "css":
				return "text/css";
				
			case "xml":
				return "application/xml";
				
			case "doc":
			case "docx":
				return "application/msword";
				
			case "xls":
			case "xlt":
			case "xlm":
			case "xld":
			case "xla":
			case "xlc":
			case "xlw":
			case "xll":
				return "application/vnd.ms-excel";
				
			case "ppt":
			case "pps":
				return "application/vnd.ms-powerpoint";
				
			case "rtf":
				return "application/rtf";
				
			case "pdf":
				return "application/pdf";
				
			case "html":
			case "htm":
			case "php":
				return "text/html";
				
			case "txt":
				return "text/plain";
				
			case "mpeg":
			case "mpg":
			case "mpe":
				return "video/mpeg";
				
			case "mp3":
				return "audio/mpeg3";
				
			case "wav":
				return "audio/wav";
				
			case "aiff":
			case "aif":
				return "audio/aiff";
				
			case "avi":
				return "video/msvideo";
				
			case "wmv":
				return "video/x-ms-wmv";
				
			case "mov":
				return "video/quicktime";
				
			case "zip":
				return "application/zip";
				
			case "tar":
				return "application/x-tar";
				
			case "swf":
				return "application/x-shockwave-flash";
				
			default:
				return "unknown/" . trim($fileSuffix[0], ".");
		}
	}
	
}

?>