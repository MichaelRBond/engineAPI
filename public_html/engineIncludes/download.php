<?php
require_once("/home/ereserves/phpincludes/engine/engineAPI/latest/engine.php");
$engine = EngineAPI::singleton();

// Tells Engine not to parse output
$engine->obCallback = FALSE;

$id = isset($engine->cleanGet['HTML']['id']) ? $engine->cleanGet['HTML']['id'] : NULL;

$fileName = isset($_SESSION['fileHandler_'.$id]['fileName']) ? $_SESSION['fileHandler_'.$id]['fileName'] : NULL;
$fileType = isset($_SESSION['fileHandler_'.$id]['fileType']) ? $_SESSION['fileHandler_'.$id]['fileType'] : NULL;
$fileData = isset($_SESSION['fileHandler_'.$id]['fileData']) ? $_SESSION['fileHandler_'.$id]['fileData'] : NULL;
$display  = isset($_SESSION['fileHandler_'.$id]['display'])  ? $_SESSION['fileHandler_'.$id]['display']  : NULL;

ob_end_clean();

if (is_empty($fileName) || is_empty($fileType) || is_empty($fileData)) {
	header("HTTP/1.0 404 Not Found");
	print 'File not found.';
	exit;
}

if ($display == "window") {
	header("Expires: 0");
	header("Cache-Control: private");
	header("Pragma: cache");
	header("Content-Length: ".strlen($fileData));
	header("Content-Type: ".$fileType);
	header("Content-Transfer-Encoding: binary");
	header('Content-Disposition: filename="'.$fileName.'"');
}
else if ($display == "download") {
	header("Expires: 0");
	header("Cache-Control: private");
	header("Pragma: cache");
	header("Content-Length: ".strlen($fileData));
	header("Content-Type: application/force-download");
	header("Content-Transfer-Encoding: binary");
	header('Content-Disposition: attachment; filename="'.$fileName.'"');
}
else {
	header("Content-Length: ".strlen($fileData));
	header("Content-Type: ".$fileType);
}

print $fileData;
?>
