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
	<!-- Favicons -->
	<link rel="icon" type="image/png" sizes="32x32" href="<?= UWEH_MAIN_URL.'favicon-32.png' ?>">
	<link rel="icon" type="image/png" sizes="16x16" href="<?= UWEH_MAIN_URL.'favicon-16.png' ?>">
	<link rel="icon" type="image/png" sizes="196x196" href="<?= UWEH_MAIN_URL.'favicon-196.png' ?>">
	<!-- OpenGraph tags -->
	<meta property="og:title" content="Uweh - Ephemeral file hosting">
	<meta property="og:type" content="website">
	<meta property="og:image" content="<?= UWEH_MAIN_URL.'riamu.png' ?>">
	<meta property="og:url" content="<?= UWEH_MAIN_URL ?>">
	<meta property="og:description" content="Temporary file hosting. Share files up to <?= Uweh\human_bytes(UWEH_MAX_FILESIZE) ?> for <?= UWEH_MAX_RETENTION_TEXT ?>.">
	<meta property="og:locale" content="en_US" />
	<!-- End of head -->
</head>
<body>
<main>
<a class="not-a-link center-text" href="<?= UWEH_MAIN_URL ?>"><h1 id="title">Uweh</h1></a>
<p id="explanation" class="center-text" id="subtitle">Share files ≤ <?= Uweh\human_bytes(UWEH_MAX_FILESIZE) ?> that disappear after <?= UWEH_MAX_RETENTION_TEXT ?></p>

<?php
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

function display_url_and_buttons ($download_url) {
	?>
	<p class="payload-msg">
		Your download link is<br><a href="<?= $download_url ?>"><?= $download_url ?></a><br>
		<button id="copy-link-btn" class="btn hidden">Copy link to clipboard</button>
		<span id="copied-placeholder"></span>
	</p>
	<div class="btn-ctn"><a class="btn not-a-link" href="<?= UWEH_MAIN_URL ?>">← Go back</a></div>
	<?php
}

function display_url_script ($download_url) {
	?>
	<script>
	(function () {
		// Copy to clpiboard only works in secure contexts aka https
		if (!navigator.clipboard) return;

		// Set behaviour
		let copy_link = document.getElementById("copy-link-btn");
		let success_span = document.getElementById("copied-placeholder");
		let success_parent = success_span.parentNode;
		let timer_id = null;
		copy_link.addEventListener("click", e => {
			clearTimeout(timer_id);
			navigator.clipboard.writeText("<?= $download_url ?>").then(() => {
				console.log("TODO successful");
			});

			// Replace child to interrupt animation
			let cloned = success_span.cloneNode(true);
			success_parent.replaceChild(cloned, success_span);
			success_span = cloned;

			success_span.textContent = "Copied !";
			success_span.classList.add("animation-copied");

			// Delete after timeout
			timer_id = setTimeout(() => {
				console.log("timeout");
				success_span.textContent = "";
				success_span.classList.remove("animation-copied");
			}, 1500);
		});
			
		// Display it
		copy_link.classList.remove("hidden");
	})();
	</script>
	<?php
}

function display_about () {
	?>
	<h3>About</h3>

	<p>Store files with a size up to <?= Uweh\human_bytes(UWEH_MAX_FILESIZE)?> for up to <?= UWEH_MAX_RETENTION_TEXT ?>.</p>
	<p>Due to malicious files being uploaded, some filetypes are not allowed such as HTML or executables.</p>
	<p>A programmatic API may be available at <?= UWEH_MAIN_URL."api.php" ?>.</p>

	<h3>Content restrictions</h3>
	<p>This website is not affiliated with the files users upload.</p>
	<p>Child pornography and other illegal files are <strong>*not*</strong> allowed, please report offending files to <a href="mailto:<?= UWEH_ABUSE_EMAIL ?>"><?= UWEH_ABUSE_EMAIL ?></a> and they will be prompty deleted.</p>

	<h3>Privacy</h3>

	<p>The website may store anonymous page hit statistics. We won't store IP addresses nor the requested filename.</p>
	<?php
}

function display_form () {
	?>
	<form id="upload-form" method="post" enctype="multipart/form-data">
		<input type="hidden" name="MAX_FILE_SIZE" value="<?= UWEH_MAX_FILESIZE ?>">
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
				<input type="text" id="name-input" name="name" minlength="1" maxlenght="<?= UWEH_LONGEST_FILENAME ?>" placeholder="eg. riamu.png">
				<br>
				or <label for="random-check">generate a random one:</label>
				<input type="checkbox" id="random-check" style="vertical-align: middle;" name="random" value="true">

			</p>
		</div>
	</form>
	<?php
}

function display_form_script () {
	?>
	<script>
	(function () {
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

		// Check if the file is a directory by trying to read it
		async function file_is_directory (file) {
			return await new Promise((resolve) => {
				let fr = new FileReader();
				let aborted = false;

				fr.addEventListener('progress', e => {
					// Order matters
					aborted = true;
					fr.abort();
				});
				fr.addEventListener('loadend', e => {
					let is_dir = fr.error !== null && !aborted;
					resolve(is_dir);
				});
				
				fr.readAsArrayBuffer(file);
			});
		}

		// File input state
		class UwehFileInput {
			constructor (file_input, upload_btn) {
				this.file_input = file_input;
				this.upload_btn = upload_btn;
			}

			addListeners() {
				this.file_input.addEventListener('change', async e => await this.checkInput());
			}

			disableInput() {
				this.file_input.classList.add('invalid-file');
				this.upload_btn.setAttribute('disabled', '');
			}

			enableInput() {
				this.file_input.classList.remove('invalid-file');
				this.upload_btn.removeAttribute('disabled');
			}

			async checkInput() {
				let files = this.file_input.files;

				let invalid_file = Array.from(files).some((v) => !(is_extension_allowed(v) && valid_file_size(v)));
				let too_many_files = files.length > 1;
				let is_single_dir = files.length == 1 && await file_is_directory(files[0]);

				if (invalid_file || too_many_files || is_single_dir) {
					this.disableInput();
				} else {
					this.enableInput();
				}
			}
		}

		// Dropzone behaviour
		class UwehDrop {
			constructor (dropzone, uweh_file_input) {
				this.dropzone = dropzone;
				this.uweh_file_input = uweh_file_input;
			}

			addListeners() {
				this.dropzone.addEventListener('drop', e => this.handleDrop(e));
				this.dropzone.addEventListener('dragenter', e => this.addDragClass());
				this.dropzone.addEventListener('dragleave', e => this.removeDragClass());
				this.dropzone.addEventListener('dragover', e => { e.preventDefault(); this.addDragClass() });
			}

			handleDrop (e) {
				this.uweh_file_input.file_input.files = e.dataTransfer.files;
				this.uweh_file_input.checkInput();

				this.removeDragClass();
				e.preventDefault();
			}

			removeDragClass() {
				this.dropzone.classList.remove('file-dragover');
			}

			addDragClass() {
				this.dropzone.classList.add('file-dragover');
			}
		}

		// Disable drag and drop on the page
		document.body.addEventListener('dragover', e => e.preventDefault());
		document.body.addEventListener('drop', e => e.preventDefault());

		// Selecting an invalid file disables the upload and highlights the input in red
		let file_input = document.getElementById('file-input');
		let upload_btn = document.getElementById('upload-btn');
		let uweh_file_input = new UwehFileInput(file_input, upload_btn);
		uweh_file_input.addListeners();
		uweh_file_input.checkInput();

		// Enable drag and drop and display text
		if ('DataTransferItem' in window) {
			let dropzone = document.getElementById('upload-it');
			let uweh_drop = new UwehDrop(dropzone, uweh_file_input);
			uweh_drop.addListeners();

			let infotext = document.getElementById('drag-drop-info');
			infotext.classList.remove('hidden');
		}
	})();
	</script>
	<?php
}

# ---

$file = $_FILES['file'] ?? null;
$about = isset($_GET['about']);

if (isset($file)) {
	$download_url = process_file($file);
	if (!is_null($download_url)) {
		display_url_and_buttons($download_url);
		display_url_script($download_url);
	}
} else if ($about) {
	display_about();
} else {
	display_form();
	display_form_script();
}

?>
</main>

<?php if (!$about) {
	echo '<p><a href="?about">About this website</a></p>';
} ?>

<p class="gen-line"><?php
	$ran_cleanup = Uweh\poor_mans_cron_cleanup(); # Run cleanup job if needed
	$ram_in_mb = round(memory_get_peak_usage()/1048576, 2);
	echo "Generated in ".Uweh\timer()."s with ".$ram_in_mb."MB by Uweh v".Uweh\VERSION.($ran_cleanup ? ".": "");
?></p>

</body>
</html>
