<?php

require_once '../src/UwehTpl.php';
require_once '../src/Uweh.php';

use Uweh\Error;

$d = [
	'page' => 'upload',

	# Page footer
	'version' => Uweh\VERSION,
	'cleanup-func' => function () { Uweh\poor_mans_cron_cleanup(); },

	# Page header
	'humanMaxFilesize' => Uweh\human_bytes(UWEH_MAX_FILESIZE),
	'maxRetentionText' => UWEH_MAX_RETENTION_TEXT,
	"mainUrl" => UWEH_MAIN_URL,

	# Html head
	"favicon-32" => UWEH_MAIN_URL.'favicon-32.png',
	"favicon-16" => UWEH_MAIN_URL.'favicon-16.png',
	"favicon-196" => UWEH_MAIN_URL.'favicon-196.png',
];

# Get data from parameters
$filepath = $_GET['path'] ?? null;
$error = $_GET['error'] ?? null;

if (isset($error)) {
	# Process error
	switch (intval($error)) {
		case Error::SOME_ERROR:
			$d['errorMessage'] = "An error occured";
			break;
		case Error::BAD_FILE:
			$d['errorMessage'] = "The file is rejected. It should be less than ".Uweh\human_bytes(UWEH_MAX_FILESIZE).", not empty and have an allowed extension.";
			break;
		case Error::UPLOAD_FAIL:
			$d['errorMessage'] = "The file upload failed";
			break;
		case Error::SERVER_ERROR:
			$d['errorMessage'] = "Server error due to a misconfiguration or a full disk. Check back later.";
			break;
		case Error::MULTIPLE_FILES:
			$d['errorMessage'] = "Multiple files were uploaded";
			break;
		default:
			$d['errorMessage'] = "An unknown error occured";
	}

} elseif (isset($filepath)) {
	# Process filepath
	if (Uweh\filepath_exists($filepath)) {
		$d['downloadUrl'] = Uweh\get_download_url($filepath);
		$d['title'] = "Uweh - File upload succeeded";
	} else {
		$d['errorMessage'] = "File doesn't exist or has expired";
	}
	
} else {
	# Nothing to do, redirecting to index
	header("Location: ". UWEH_MAIN_URL);
	exit;
}

if (!isset($d['title'])) {
	$d['title'] = "Uweh - File upload failed";
}

# === Render page ===

UwehTpl\php_html_header($d);

UwehTpl\html_start('body');
UwehTpl\html_start('main');

	UwehTpl\body_header($d);
	UwehTpl\result_page($d);

UwehTpl\html_end('main');

UwehTpl\riamu_picture($d);
UwehTpl\page_javascript($d);
UwehTpl\page_footer($d);
UwehTpl\generation_line($d);

UwehTpl\html_end('body');
UwehTpl\html_end('html');
