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
	<meta name=description content="Share files up to <?= Uweh\human_bytes(UWEH_MAX_FILESIZE) ?> for <?= UWEH_MAX_RETENTION_TEXT ?>.">
	<link rel="icon" type="image/png" href="favicon.png"/>
	<script>/**/</script> <!-- Prevent FOUC in Firefox -->
	<!-- OpenGraph tags -->
	<meta property="og:title" content="Uweh - Ephemeral file hosting">
	<meta property="og:type" content="website">
	<meta property="og:image" content="<?= UWEH_MAIN_URL . 'img/riamu.png' ?>">
	<meta property="og:url" content="<?= UWEH_MAIN_URL ?>">
	<meta property="og:description" content="Share files up to <?= Uweh\human_bytes(UWEH_MAX_FILESIZE) ?> for <?= UWEH_MAX_RETENTION_TEXT ?>.">
	<meta property="og:locale" content="en_US" />
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
		die("Multiple files ??"); # FIXME
	}
	
	$name = $_POST['name'] ?? "";
	$random = $_POST['random'] ?? null;
	$random = isset($random) ? (bool) strlen($random) : False;
	
	try {
		$filename = Uweh\process($file, array(
			"random" => $random,
			"name" => $name,
		));
		
		$download_url = Uweh\get_download_url($filename);
		$pretty_url = Uweh\get_pretty_download_url($filename);
		
		echo "<p class=\"payload-msg\">Your download link is<br><a href=\"$download_url\">$pretty_url</a></p>";
		if ($download_url !== $pretty_url) {
			echo "<p class=\"payload-msg\">or <a href=\"$download_url\">$download_url</a></p>";
		}
	
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
			fatal("An error occured");
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
			<button type="submit" class="upload-btn">Upload file</button>
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

<?php
# Run cleanup job
$ran_cleanup = Uweh\poor_mans_cron_cleanup();
?>
<p class="gen-line"><?= "Generated in ".Uweh\timer()."s with ".round(memory_get_peak_usage()/1048576, 2)."MB".($ran_cleanup ? ".": "") ?></p>

</body>
</html>
