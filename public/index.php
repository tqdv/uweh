<?php

require_once '../src/UwehTpl.php';
require_once '../src/Uweh.php';

use Uweh\Error;

/** Save the uploaded file according to POST arguments, and return its filepath.
 * 
 * Given an uploaded file and (optional) POST arguments, save it using `Uweh\save_file`
 * and return the storage filepath as an array of [$filepath, null].
 * On failure, return the `Uweh\Error` code as [null, $error_code]
 * 
 * @param $file The $_FILES file to save
 * @return array [filepath: ?string, error: ?int]
 */
function process_file ($file) : array {
	if (! Uweh\is_single_file($file)) {
		return [null, Error::MULTIPLE_FILES];
	}
	
	$name = $_POST['name'] ?? "";
	$random = $_POST['random'] ?? null;
	$random = isset($random) ? (bool) strlen($random) : False;
	
	try {
		$filepath = Uweh\save_file($file, array(
			"random" => $random,
			"name" => $name,
		));
		
		return [$filepath, null];
	
	} catch (Exception $e) {
		return [null, Error::categorize($e)];
	}

	return [null, Error::SOME_ERROR];
}

# === Handle file upload (POST) ===

$file = $_FILES['file'] ?? null;
if (isset($file)) {
	# Process file and redirect (Post-Redirect-Get pattern to prevent form resubmission)

	[$filepath, $err] = process_file($file);

	# Pass data through the GET parameters
	if (isset($filepath)) {
		$url = UWEH_MAIN_URL . "upload.php?path=" . rawurlencode($filepath);
	} else {
		$url = UWEH_MAIN_URL . "upload.php?error=" . rawurlencode($err);
	}

	# Redirect
	header("Location: $url", True, 303);
	exit;
}

# === Set template data ===

$d = [
	'page' => 'index',

	# Page footer
	'version' => Uweh\VERSION,
	'cleanup-func' => function () { Uweh\poor_mans_cron_cleanup(); },

	# Page header
	"mainUrl" => UWEH_MAIN_URL,
	'humanMaxFilesize' => Uweh\human_bytes(UWEH_MAX_FILESIZE),
	'maxRetentionText' => UWEH_MAX_RETENTION_TEXT,
	
	# Html head
	"title" => "Uweh - Ephemeral file hosting",
	"description" => "Temporary file hosting. Share files up to " . Uweh\human_bytes(UWEH_MAX_FILESIZE) . " for " . UWEH_MAX_RETENTION_TEXT . ".",
	"canonical" => UWEH_MAIN_URL,
	"favicon-32" => UWEH_MAIN_URL.'favicon-32.png',
	"favicon-16" => UWEH_MAIN_URL.'favicon-16.png',
	"favicon-196" => UWEH_MAIN_URL.'favicon-196.png',
	"og:image" => UWEH_MAIN_URL."riamu.png",
	
	# Upload form html (and javascript)
	"filteringMode" => UWEH_EXTENSION_FILTERING_MODE,
	"maxFilesize" => UWEH_MAX_FILESIZE,
	"longestFilename" => UWEH_LONGEST_FILENAME,
];

# Upload form javascript (continued)
if (UWEH_EXTENSION_FILTERING_MODE === 'GRANTLIST') {
	$extlist = implode(',', UWEH_EXTENSION_GRANTLIST);
} else if (UWEH_EXTENSION_FILTERING_MODE === 'NONE') {
	$extlist = "";
} else { # 'BLOCKLIST'
	$extlist = implode(',', UWEH_EXTENSION_BLOCKLIST);
}
$d['extlist'] = $extlist;

# === Render page ===

UwehTpl\php_html_header($d);

UwehTpl\html_start('body');
UwehTpl\html_start('main');

	UwehTpl\body_header($d);
	UwehTpl\html_upload_form($d);

UwehTpl\html_end('main');

UwehTpl\riamu_picture($d);
UwehTpl\page_javascript($d);
UwehTpl\page_footer($d);
UwehTpl\generation_line($d);

UwehTpl\html_end('body');
UwehTpl\html_end('html');
