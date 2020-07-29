<?php
require_once '../src/Uweh.php';
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Uweh - About</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!-- Maybe OpenGraph tags ?-->
	<link rel="stylesheet" href="main.css">
	<link rel="canonical" href="<?= UWEH_MAIN_URL ?>">
	<link rel="icon" type="image/png" href="favicon.png"/>
	<script>/**/</script> <!-- Prevent FOUC in Firefox -->
</head>
<body>
<main>

<a class="not-a-link center-text" href="<?= UWEH_MAIN_URL ?>"><h1 id="title">Uweh</h1></a>
<p id="explanation" class="center-text" id="subtitle">Share files ≤ <?= Uweh\human_bytes(UWEH_MAX_FILESIZE) ?> that disappear after <?= UWEH_MAX_RETENTION_TEXT ?></p>

<h3>About</h3>

<p>Store files with a size up to <?= Uweh\human_bytes(UWEH_MAX_FILESIZE)?> for up to <?= UWEH_MAX_RETENTION_TEXT ?>.</p>
<p>Due to malicious files being uploaded, some filetypes are not allowed such as HTML or executables.</p>
<p>A programmatic API may be available at <?= UWEH_MAIN_URL."api.php" ?>.</p>

<h3>Content restrictions</h3>
<p>This website is not affiliated with the files users upload.</p>
<p>Child pornography and other illegal files are <strong>*not*</strong> allowed, please report offending files to <a href="mailto:abuse@example.com">abuse@example.com</a> and they will be prompty deleted.</p>

<h3>Privacy</h3>

<p>The website may store anonymous page hit statistics. We won't store IP addresses nor the requested filename.</p>

</main>
</body>
</html>