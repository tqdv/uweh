<?php

# Uweh html templates
namespace UwehTpl;

// Escapes double quotes and backslashes with a backslash
function qq_escape (string $s) : string {
	$s = str_replace("\\", "\\\\", $s);
	$s = str_replace("\"", "\\\"", $s);
	return $s;
}

/** Flip flop timer that rounds to 2 decimal places.
 * 
 * From <https://stackoverflow.com/a/4412766/5226686>
 */
function timer () : ?float {
	static $start;
	if (is_null($start)) {
		$start = microtime(true);
		return null;
	} else {
		$diff = ceil((microtime(true) - $start) * 100) / 100;
		$start = null;
		return $diff;
	}
}

// Start the time as soon as possible
timer();

// Keys: version
function generation_line ($d) {
	echo '<p class="gen-line">';
		$ran_cleanup = $d['cleanup-func'](); # Run cleanup job if needed

		$duration = timer();
		$ram_in_mb = round(memory_get_peak_usage()/1048576, 2);
		$version = $d['version'];
		$cleanup_dot = ($ran_cleanup ? '.' : '');

		echo "Generated in ${duration}s with ${ram_in_mb}MB by Uweh v${version}${cleanup_dot}";
	echo '</p>';
}

/**
 * Prints the html <head> element as well as the doctype and html tag.
 * 
 * We dump the CSS into the head if the page is the index.
 * 
 * @param array $d has keys: 
 *              - 'title' for the html <title>
 *              - 'description' for <meta name=description>
 *              - 'canonical' for the canonical url
 *              - 'favicon-32', 'favicon-16' and 'favicon-196' for favicon urls
 *              - 'og:image' for opengraph tags
 *              - (optional) 'endofhead' for the extra html to insert before the end of <head>
 */
function php_html_header ($d) {
	header('Content-Type: text/html; charset=utf-8');

	$desc = htmlspecialchars($d['description']);
	$canon = htmlspecialchars($d['canonical']);
	$title = htmlspecialchars($d['title']);

	?><!DOCTYPE html>
	<html lang="en">
	<head>
		<meta charset="utf-8">
		<title><?= $title ?></title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<!-- Stylesheet -->
		<?php if ($d['page'] === 'index') {
			/* Dump main.css and defer CSS loading (since we dumped it) */ ?>
			<style><?= file_get_contents(dirname(__FILE__, 2) . '/public/main.css') ?></style>
			<link rel="stylesheet" href="main.css" media="print" onload="this.media='all'">
		<?php } else { ?>
			<link rel="preload" href="main.css" as="style">
			<link rel="stylesheet" href="main.css">
		<?php } ?>
		<!-- Fetch script early -->
		<script id="deferred-main-js" defer src="main.js"></script>
		<!-- Metadata -->
		<link rel="canonical" href="<?= $canon ?>">
		<meta name=description content="<?= $desc ?>">
		<!-- Favicons -->
		<link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars($d['favicon-32']) ?>">
		<link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars($d['favicon-16']) ?>">
		<link rel="icon" type="image/png" sizes="196x196" href="<?= htmlspecialchars($d['favicon-196']) ?>">
		<!-- OpenGraph tags -->
		<meta property="og:title" content="<?= $title ?>">
		<meta property="og:type" content="website">
		<meta property="og:image" content="<?= htmlspecialchars($d['og:image']) ?>">
		<meta property="og:url" content="<?= $canon ?>">
		<meta property="og:description" content="<?= $desc ?>">
		<meta property="og:locale" content="en_US" />
		<?php /* Others */ echo $d['endofhead'] ?? ""; ?>
	</head>
	<?php
}

// Keys: mainUrl, humanMaxFilesize, maxRetentionText
function body_header ($d) {
	?>
	<a class="not-a-link center-text" href="<?= $d['mainUrl'] ?>"><h1 id="title">Uweh</h1></a>
	<p id="explanation" class="center-text" id="subtitle">Share files ≤ <?= $d['humanMaxFilesize'] ?> that disappear after <?= $d['maxRetentionText'] ?></p>
	<?php
}

function page_footer ($d) {
	?>
	<p><a href="about.php">About this website</a></p>
	<?php
}

// Display the upload form.
// Keys: maxFilesize, longestFilename
function html_upload_form ($d) {
	?>
	<form id="upload-form" method="post" enctype="multipart/form-data">
		<input type="hidden" name="MAX_FILE_SIZE" value="<?= $d['maxFilesize'] ?>">
		<div id="upload-it">
			<div id="file-form">
				<label for="file-input" class="no-click">Select file to upload: </label>
				<input type="file" id="file-input" name="file" required>
				<span id="drag-drop-info" class="hidden">… or drag and drop the file here</span>
			</div>
			<button type="submit" id="upload-btn" class="btn">Upload file</button>
		</div>
		
		<div id="upload-options">
			<h3 id="extra-options">Extra options:</h3>
			<p>
				<label for="name-input">Use custom filename: </label>
				<input type="text" id="name-input" name="name" minlength="1" maxlenght="<?= $d['longestFilename'] ?>" placeholder="eg. riamu.png">
				<br>
				or <label for="random-check">generate a random one:</label>
				<input type="checkbox" id="random-check" style="vertical-align: middle;" name="random" value="true">

			</p>
		</div>
	</form>
	<?php
}

// Print about text
// Keys: humanMaxFilesize, maxRetentionText, mainUrl, abuseEmail
function about_text ($d) {
	$email = htmlspecialchars($d['abuseEmail'])

	?>
	<h3>About</h3>

	<p>Store files with a size up to <?= $d['humanMaxFilesize'] ?> for up to <?= $d['maxRetentionText'] ?>.</p>
	<p>Due to malicious files being uploaded, some filetypes are not allowed such as HTML or executables.</p>
	<p>A programmatic API may be available at <?= $d['mainUrl']."api.php" ?>.</p>

	<h3>Content restrictions</h3>
	<p>This website is not affiliated with the files users upload.</p>
	<p>
		Child pornography and other illegal files are <strong>*not*</strong> allowed,
		please report offending files to <a href="mailto:<?= $email ?>"><?= $email ?></a> and they will be prompty deleted.
	</p>

	<h3>Privacy</h3>

	<p>The website may store anonymous page hit statistics. We won't store IP addresses nor the requested filename.</p>
	<?php
}

// Keys: downloadUrl, mainUrl
function html_download_url ($d) {
	$download_url = htmlspecialchars($d['downloadUrl'])
	?>
	<p class="payload-msg">
		Your download link is<br><a href="<?= $download_url ?>"><?= $download_url ?></a><br>
		<button id="copy-link-btn" class="btn hidden">Copy link to clipboard</button>
		<span id="copied-placeholder"></span>
	</p>
	<div class="btn-ctn"><a class="btn not-a-link" href="<?= $d['mainUrl'] ?>">← Go back</a></div>
	<?php
}

function riamu_picture ($d) {
	?>
	<picture id="riamu" >
		<source srcset="riamu.webp" type="image/webp">
		<img src="riamu.png">
	</picture>
	<?php
}

// Displays the script for the current page
// It dumps the JS into the HTML for the index page, and waits for the script in the header on other pages
// Keys: page, { filteringMode, extlist, maxFilesize } for index, { downloadUrl } for upload
function page_javascript ($d) {
	$page = $d['page'];

	if ($page == 'about') return;

	echo "<script>\n";
	
	if ($page === 'index') { ?>
		var Uweh = Uweh || {};
		Uweh.php = Uweh.php || {};

		// PHP -> JS
		Uweh.php.filteringMode = "<?= qq_escape($d['filteringMode']) ?>";
		Uweh.php.extlist = "<?= qq_escape($d['extlist'] ?? "") ?>";
		Uweh.php.maxFilesize = "<?= qq_escape($d['maxFilesize']) ?>";

		<?php /* Dump main.js */ echo file_get_contents(dirname(__FILE__, 2) . '/public/main.js'); ?>

		(function () {
			
			Uweh.disable_drag_drop();

			// Selecting an invalid file disables the upload and highlights the input in red
			let file_input = document.getElementById('file-input');
			let upload_btn = document.getElementById('upload-btn');
			let uweh_file_input = Uweh.enableFileInputCheck(file_input, upload_btn);
			uweh_file_input.checkInput();

			// Drag and drop a file over the input field
			let dropzone = document.getElementById('upload-it');
			let infotext = document.getElementById('drag-drop-info');
			Uweh.enableDragDrop(dropzone, uweh_file_input, infotext);

		})();

	<?php } elseif ($page === 'upload') { ?>
		(function () {

			let main_js = document.getElementById('deferred-main-js');
			main_js.addEventListener("load", () => {
				Uweh.php.downloadUrl = "<?= qq_escape($d['downloadUrl']) ?>";
				let copy_link = document.getElementById("copy-link-btn");
				let success_span = document.getElementById("copied-placeholder");
				Uweh.enableCopyLinkButton(copy_link, success_span);
			});

		)();
	<?php }

	echo "</script>";
}

function html_start ($s) {
	echo "<$s>";
}

function html_end ($s) {
	echo "</$s>";
}
