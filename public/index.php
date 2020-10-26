<?php
require_once '../src/Uweh.php';
header('Content-Type: text/html; charset=utf-8');
Uweh\timer();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Uweh - Ephemeral file hosting</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="main.css">
	<link rel="canonical" href="<?= UWEH_MAIN_URL ?>">
	<meta name=description content="Temporary file hosting. Share files up to <?= Uweh\human_bytes(UWEH_MAX_FILESIZE) ?> for <?= UWEH_MAX_RETENTION_TEXT ?>.">
	<link rel="icon" type="image/png" href="favicon.png"/>
	<!-- OpenGraph tags -->
	<meta property="og:title" content="Uweh - Ephemeral file hosting">
	<meta property="og:type" content="website">
	<meta property="og:image" content="<?= UWEH_MAIN_URL . 'img/riamu.png' ?>">
	<meta property="og:url" content="<?= UWEH_MAIN_URL ?>">
	<meta property="og:description" content="Temporary file hosting. Share files up to <?= Uweh\human_bytes(UWEH_MAX_FILESIZE) ?> for <?= UWEH_MAX_RETENTION_TEXT ?>.">
	<meta property="og:locale" content="en_US" />
	<script>0</script> <!-- Prevent FOUC in Firefox -->
</head>
<body>
<main>
<a class="not-a-link center-text" href="<?= UWEH_MAIN_URL ?>"><h1 id="title">Uweh</h1></a>
<p id="explanation" class="center-text" id="subtitle">Share files ≤ <?= Uweh\human_bytes(UWEH_MAX_FILESIZE) ?> that disappear after <?= UWEH_MAX_RETENTION_TEXT ?></p>

<?php
use Uweh\Error;
function fatal (string $msg) {
	echo '<p class="payload-msg error-msg">Error: '.htmlspecialchars($msg).'</p>';
}

$file = $_FILES['file'] ?? null;

if (isset($file)) {
# Process file
	if (! Uweh\is_single_file($file)) {
		fatal("Multiple files were uploaded");
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
		
		echo "<p class=\"payload-msg\">Your download link is<br><a href=\"$download_url\">$download_url</a></p>";
	
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
	
	# Display back button
	echo '<div class="button-ctn"><a class="upload-btn not-a-link" href="'.UWEH_MAIN_URL.'">Go back</a><div>';

} else {
# Display form
?>
	<form id="upload-form" method="post" enctype="multipart/form-data">
		<input type="hidden" name="MAX_FILE_SIZE" value="<?= UWEH_MAX_FILESIZE ?>">
		<div id="upload-it">
			<span id="file-form">
				<label for="file-input" class="no-click">Select file to upload: </label><br>
				<input type="file" id="file-input" name="file" required>
			</span>
			<button type="submit" id="upload-btn">Upload file</button>
		</div>
		
		<div id="upload-options">
			<h3 id="extra-options">Extra options:</h3>
			<p>
				<label for="name-input">Use custom filename: </label>
				<input type="text" id="name-input" name="name" minlength="1" maxlenght="<?= UWEH_LONGEST_FILENAME ?>" placeholder="eg. riamu.png">
				<br>
				or <label for="random-check">generate a random one:</label>
				<input type="checkbox" id="random-check" style="vertical-align: middle;" name="random" value="true">

			</p>
		</div>
	</form>

<?php
}
?>
</main>
<p><a href="about.php">About this website</a></p>

<p class="gen-line"><?php
	$ran_cleanup = Uweh\poor_mans_cron_cleanup(); # Run cleanup job if needed
	$ram_in_mb = round(memory_get_peak_usage()/1048576, 2);
	echo "Generated in ".Uweh\timer()."s with ".$ram_in_mb."MB by Uweh v".Uweh\VERSION.($ran_cleanup ? ".": "");
?></p>

<script>
(function () {

let file_input = document.getElementById('file-input');
let upload_btn = document.getElementById('upload-btn');

function is_extension_allowed(file) {
	let i = file.name.lastIndexOf('.');
	let ext = file.name.substring(i + 1); // If i == -1, then it still works
	
	return <?php
		if (UWEH_EXTENSION_FILTERING_MODE === 'GRANTLIST') {
			$extlist = '"'.implode(',', UWEH_EXTENSION_GRANTLIST).'"';
			echo "${extlist}.split(',').includes(ext)";
		} else if (UWEH_EXTENSION_FILTERING_MODE === 'NONE') {
			echo "True";
		} else { # if (UWEH_EXTENSION_FILTERING_MODE === 'BLOCKLIST')
			$extlist = '"'.implode(',', UWEH_EXTENSION_BLOCKLIST).'"';
			echo "! ${extlist}.split(',').includes(ext)";
		}
	?>;
}

function valid_file_size (file) {
	return 0 < file.size && file.size <= <?= UWEH_MAX_FILESIZE ?>;
}

function check_file_input (file_input) {
	let invalid_file = Array.from(file_input.files).some((v) => !(is_extension_allowed(v) && valid_file_size(v)));
	if (invalid_file) {
		file_input.classList.add('invalid-file');
		upload_btn.setAttribute('disabled', '');
	} else {
		file_input.classList.remove('invalid-file');
		upload_btn.removeAttribute('disabled');
	}
}

// Selecting an invalid file disables the upload and highlights the input in red
file_input.addEventListener('change', e => check_file_input(e.target));
check_file_input(file_input);

})();
</script>

</body>
</html>
