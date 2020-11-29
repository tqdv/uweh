<?php
require_once '../src/Uweh.php';

use Uweh\Error;

header('Content-Type: text/plain; charset=UTF-8');

function fatal (string $message) {
	echo "Error: $message\n";
	exit;
}

# Only work on ?do=upload
if (($_GET['do'] ?? null) !== 'upload') {
	echo "Example usage: curl -i -F name=test.jpg -F file=@localfile.jpg ".UWEH_MAIN_URL."api.php?do=upload\n";
	exit;
}

# Process arguments

$file = $_FILES['file'] ?? null;
if (!isset($file)) {
	fatal("Missing file");
}
if (!Uweh\is_single_file($file)) {
	fatal("Bad file upload");
}

$name = $_POST['name'] ?? "";
$random = ($_POST['random'] ?? "") != "";

# Try to save the file
try {
	$filepath = Uweh\save_file($file, array(
		"random" => $random,
		"name" => $name,
	));
	echo Uweh\get_download_url($filepath);

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
