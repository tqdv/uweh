<?php

require_once '../src/UwehTpl.php';
require_once '../src/Uweh.php';

use Uweh\Error;

# Functions

function fatal (string $msg) {
	echo '<p class="payload-msg error-msg">Error: '.htmlspecialchars($msg).'</p>';
}

# Returns the download url
function process_file ($file) : ?string {
	if (! Uweh\is_single_file($file)) {
		fatal("Multiple files were uploaded");
		return null;
	}
	
	$name = $_POST['name'] ?? "";
	$random = $_POST['random'] ?? null;
	$random = isset($random) ? (bool) strlen($random) : False;
	
	try {
		$filepath = Uweh\save_file($file, array(
			"random" => $random,
			"name" => $name,
		));
		
		$download_url = Uweh\get_download_url($filepath);
		return $download_url;
	
	} catch (Exception $e) {
		switch (Error::categorize($e)) {
		case Error::SOME_ERROR:
			fatal("An error occured"); break;
		case Error::BAD_FILE:
			fatal("The file is rejected. It should be less than ".Uweh\human_bytes(UWEH_MAX_FILESIZE).", not empty and have an allowed extension."); break;
		case Error::UPLOAD_FAIL:
			fatal("The file upload failed"); break;
		case Error::SERVER_ERROR:
			fatal("Server error due to a misconfiguration or a full disk. Check back later."); break;
		default:
			fatal("An unknown error occured");
		}
	}

	return null;
}

# ---

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
	
	# Upload form (and javascript)
	"filteringMode" => UWEH_EXTENSION_FILTERING_MODE,
	"maxFilesize" => UWEH_MAX_FILESIZE,
	"longestFilename" => UWEH_LONGEST_FILENAME,
];

if (UWEH_EXTENSION_FILTERING_MODE === 'GRANTLIST') {
	$extlist = implode(',', UWEH_EXTENSION_GRANTLIST);
} else if (UWEH_EXTENSION_FILTERING_MODE === 'NONE') {
	$extlist = "";
} else { # 'BLOCKLIST'
	$extlist = implode(',', UWEH_EXTENSION_BLOCKLIST);
}
$d['extlist'] = $extlist;

# ---

UwehTpl\php_html_header($d);

UwehTpl\html_start('body');
UwehTpl\html_start('main');

	UwehTpl\body_header($d);

	$file = $_FILES['file'] ?? null;

	if (isset($file)) {
		$download_url = process_file($file);
		if (!is_null($download_url)) {
			$d['downloadUrl'] = $download_url;
			UwehTpl\html_download_url($d);
		}
	} else {
		UwehTpl\html_upload_form($d);
	}

UwehTpl\html_end('main');

UwehTpl\riamu_picture($d);
UwehTpl\page_javascript($d);
UwehTpl\page_footer($d);
UwehTpl\generation_line($d);

UwehTpl\html_end('body');
UwehTpl\html_end('html');
