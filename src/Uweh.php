<?php

namespace Uweh;

use \Exception;

require_once 'config.php';

# Entry point. Steps: check input, generate filename, save file, return filename
# We assume that our arguments have the right types
# (single_file_array $file, [bool $str, string $name]) → string $filename throws BadFileExtension | SaveFail | FileException
# 
function process (array $file, array $flags = array()) {
	check_file($file); # check filesize and errors
	
	$name = $file['name'] ?? ""; # File name supplied
	if (strlen($flags['name'])) { $name = $flags['name']; }
	$name = sanitize_name($name); # also truncates it
	
	$gen_random = ($flags['random'] ?? False) || strlen($name) == 0;
	
	# Prepare filename generation
	#:my (?string $fileext, string $suffix);
	if ($gen_random) {
		# Differentiate between no extension and empty extension
		$fileext = pathinfo($name)['extension'] ?? null;
		if (isset($fileext)) {
			$fileext = shorten_name($fileext); # because same limits as filename
		}
		$suffix = isset($fileext) ? ".$fileext" : "";
	} else {
		$name = shorten_name($name);
		$fileext = pathinfo($name)['extension'] ?? null;
		$suffix = "_$name";
	}
	# OK $fileext, $suffix
	
	# Deter people from uploading problematic files
	# NB but they can just rename the file or supply a valid custom name
	# NB This can fail if the filename is shortened to an invalid extension
	if (isset($fileext) && !is_extension_allowed($fileext)) {
		throw new BadFileExtension($fileext);
	}
	
	# Generate filename
	$fileroot = make_absolute(UWEH_FILES_PATH);
	do {
		$filename = gen_prefix() . $suffix;
	} while (file_exists($fileroot.$filename));
	
	# Save file
	$success = move_uploaded_file($file['tmp_name'], $fileroot.$filename);
	if (!$success) {
		throw new SaveFail($name);
	}
	
	return $filename;
}

/* Actions */

function get_download_url (string $filename) {
	return UWEH_DOWNLOAD_URL.rawurlencode($filename);
}

function get_pretty_download_url (string $filename) {	
	return UWEH_DOWNLOAD_URL.pretty_urlencode($filename);
}

# Try to run poor man's cron cleanup job based on the configuration
# Returns whether it ran the cleanup job
function poor_mans_cron_cleanup () {
	$ran_cleanup = False;
	if (POOR_MAN_CRON_INTERVAL) {
		$mydir = dirname(__FILE__);
		$previous = file_get_contents($mydir."/lastrun.txt");
		if (!$previous || time() - $previous >= POOR_MAN_CRON_INTERVAL * 60) {
			$ran_cleanup = True;
			exec("sh \"$mydir/clean_files.sh\"");
			file_put_contents($mydir."/lastrun.txt", time());
		}
	}
	return $ran_cleanup;
}

/* Error handling */

# cf. https://www.php.net/manual/en/features.file-upload.errors.php
class FileException extends Exception {
	public /*int*/ $error_code;
	function __construct (int $error_code) {
		parent::__construct("Uploaded file errored with code $error_code");
		$this->error_code = $error_code;
	}
}

class BadFileExtension extends Exception {
	public /*string*/ $extension;
	function __construct (string $extension) {
		parent::__construct("Bad file extension '$extension'");
		$this->extension = $extension;
	}
}

class SaveFail extends Exception {
	public /*string*/ $filename; # User-supplied
	function __construct (string $filename) {
		parent::__construct("Failed to save file '$filename'");
		$this->filename = $filename;
	}
}

# Check file size and error code
# … → () throws Uweh\FileException
function check_file (array $file) {
	$error = $file['error'];
	# Check file size
	if (!$error && $file['size'] > UWEH_MAX_FILESIZE) {
		$error = UPLOAD_ERR_FORM_SIZE;
	}
	if ($error) {
		throw new FileException($error);
	}
}

/* Input sanitizing */

# Remove invalid characters from a filename
function sanitize_name (string $name) {
	# NB this may modify the extension
	# CHECKME A bit arbitrary cf. \
	return str_replace(array("\0", "/", "\\"), "", $name);
}

# Truncate the string
function shorten_name (string $s) {
	if (strlen($s) <= UWEH_LONGEST_FILENAME) {
		return $s;
	}
	if (function_exists('mb_strcut')) {
		return mb_strcut($s, 0, UWEH_LONGEST_FILENAME, 'UTF-8'); # mb_* is slower, but correct
	} else {
		return substr($s, 0, UWEH_LONGEST_FILENAME);
	}
}

/* Logic */

# Generates the random file prefix
function gen_prefix () {
	$chars = 'abdefghjknopqstuvxyzABCDEFHJKLMNPQRSTUVWXYZ345679'; # Removed homoglyphs
	$name = '';
	for ($i = 0; $i < UWEH_PREFIX_LENGTH; $i++) {
		$name .= $chars[mt_rand(0, 48)];
	}
	return $name;
}

# Tests if the file extension is allowed
function is_extension_allowed (string $ext) {
	$ext = strtolower($ext);
	if (UWEH_EXTENSION_FILTERING_MODE === 'BLOCKLIST') {
		return !in_array($ext, UWEH_EXTENSION_BLOCKLIST);
	}
	else if (UWEH_EXTENSION_FILTERING_MODE === 'GRANTLIST') {
		return in_array($ext, UWEH_EXTENSION_GRANTLIST);
	} 
	else if (UWEH_EXTENSION_FILTERING_MODE === 'NONE'){
		return True;
	} else {
		return True; # TODO Warn ?
	}
}

# Encode only the reserved symbols, might not work
function pretty_urlencode (string $s) {
	$raw = array('!', '#', '$', '%', '&', '\'', '(', ')', '*', '+', ',', '/', ':', ';', '=', '?', '@', '[', ']');
	$encoded = array('%21', '%23', '%24', '%25', '%26', '%27', '%28', '%29', '%2A', '%2B', '%2C', '%2F', '%3A', '%3B', '%3D', '%3F', '%40', '%5B', '%5D');
	
	return str_replace($raw, $encoded, $s);
}

# Turns a path relative to the repo root into an absolute path
function make_absolute (string $p) {
	if (strlen($p) && $p[0] !== '/') {
		return  dirname(__FILE__, 2) . "/" . $p;
	} else {
		return $p;
	}
}

/* Utilities */

# Tests if a $*FILES[$name] array is a single file (and not multiple)
function is_single_file (array $file) {
	return is_int($file['size']);
}

# Error codes for error_category
class Error {
	const SOME_ERROR   = -1; # Generic error
	const TOO_LARGE	= 1; # File too large
	const NO_FILE	  = 2; # No file uploaded (correctly)
	const SERVER_ERROR = 3; # Server error (IO generally)
	const BAD_FILE	 = 4; # File not approved
}

function error_category ($e) {
	if ($e instanceof FileException) {
		switch ($e->error_code) {
		case 1:
		case 2:
			return Error::TOO_LARGE; break;
		case 3:
		case 4:
			return Error::NO_FILE; break;
		case 6:
		case 7:
			return Error::SERVER_ERROR; break;
		case 8:
		default:
			return Error::SOME_ERROR;
		}
	} else if ($e instanceof SaveFail) {
		return Error::SERVER_ERROR;
	} else if ($e instanceof BadFileExtension) {
		return Error::BAD_FILE;
	} else {
		return Error::SOME_ERROR;
	}
}

# Convert a number of bytes into a human readable format
# integer part < 1024, 1 decimal figure
function human_bytes (int $n) {
	$units = array("bytes", "kB", "MB", "GB", "TB");
	$i = 0;
	for ($i = 0; $i < 4; $i++) {
		if ($n < 1024) {
			break;
		} else {
			$n /= 1024;
		}
	}
	$n10 = floor($n * 10) / 10; # Truncate to 1 decimal place
	return $n . ' ' . $units[$i];
}

/* From https://stackoverflow.com/a/4412766/5226686 */
# Takes the ceiling at 2 decimal places
function timer () {
	static $start;

	if (is_null($start)) {
		$start = microtime(true);
	} else {
		$diff = ceil((microtime(true) - $start) * 100) / 100;
		$start = null;
		return $diff;
	}
}
