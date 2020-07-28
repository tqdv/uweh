<?php
require_once '../src/Uweh.php';

use Uweh\Error;

header('Content-Type: text/plain; charset=UTF-8');

function fatal (string $message) {
	echo "Error: $message\n";
	exit(0);
}

# Only work on ?do=upload
if (!isset($_GET['do']) || $_GET['do'] !== 'upload') {
	echo "Example usage: curl -i -F name=test.jpg -F file=@localfile.jpg ".UWEH_MAIN_URL."api.php?do=upload\n";
	exit(0);
}

# Process arguments

$file = $_FILES['file'] ?? null;
if (!isset($file)) {
	fatal("Missing file");
}
if (! Uweh\is_single_file($file)) {
	fatal("Bad file upload");
}

$name = $_POST['name'] ?? "";
$random = $_POST['random'] ?? null;
$random = isset($random) ? (bool) strlen($random) : False;

# Try to save the file
try {
	$filename = Uweh\process($file, array(
		"random" => $random,
		"name" => $name,
	));
	echo Uweh\get_download_url($filename);

} catch (Exception $e) {
	switch (Uweh\error_category($e)) {
	case Error::SOME_ERROR:
		fatal("An error occured"); break;
	case Error::TOO_LARGE:
		fatal("File is too large. Should be less than ".Uweh\human_bytes(UWEH_MAX_FILESIZE)."."); break;
	case Error::NO_FILE:
		fatal("No file was succesfully uploaded"); break;
	case Error::SERVER_ERROR:
		fatal("Server error due to a misconfiguration or a full disk. Check back later."); break;
	case Error::BAD_FILE:
		fatal("File was rejected because its file extension is blocked"); break;
	default:
		exit(0);
	}
}