<?php

namespace Uweh;

use \Exception;
require_once 'config.php';

const VERSION = "2.1";

const PREFIX_ALPHABET = 'abdefghjknopqstuvxyzABCDEFHJKLMNPQRSTUVWXYZ345679'; # 49 letters without homoglyphs
const PREFIX_ALPHABET_LAST = 48; # == strlen(ALPHABET) - 1
const PREFIX_LENGTH = 2; # 49**2 = 2_401 < 10_000 subfolders per folder (rough estimate)

/** Moves the uploaded file to storage. This is the application's main function.
 * 
 * NB The file is deleted by PHP if it is not moved to storage cf. [php manual](https://www.php.net/manual/en/features.file-upload.post-method.php).
 * 
 * Function outline:
 * - Check that the file is valid
 * - Choose the filename
 * - Prepare destination folder
 * - Move the file to it
 * - Return the stored file path
 * 
 * `$flags` array keys:

 * 
 * @param array $file The uploaded file as a single file $_FILES array
 * @param array $flags (optional) Function options. Keys:
 *                     - `random => Bool`: Generate a random filename 
 *                     - `name => Str`: Use this filename
 * @return string Filepath relative to files folder
 * @throws UploadError if the upload failed
 * @throws FileTooBig if the uploaded file exceeds limits
 * @throws EmptyFile if the uploaded file is empty
 * @throws BadFileExtension if the file extension is invalid
 * @throws FilenameCollision if it fails to create a unique filename
 * @throws SaveFail if the file failed to be saved
 */
function save_file (array $file, array $flags = array()) : string {
	check_file($file); # => UploadError | FileTooBig | EmptyFile
	
	# Prepare parameters
	$name = $file['name'] ?? "";
	if (($flags['name'] ?? "") != "") $name = $flags['name'];
	$name = sanitize_name($name);
	$gen_random = ($flags['random'] ?? False) || strlen($name) == 0;

	# Check the extension of the initial filename
	check_extension($name); # => BadFileExtension

	# Compute filename
	if ($gen_random) { $filename = gen_random_name($name); }
	else {
		[$filename, $recheck_ext] = shorten_name($name);
		if ($recheck_ext) {
			try { check_extension($name); } catch (BadFileExtension $e) {
				$filename = gen_random_name(); # If all else fails, use random filename
			}
		}
	}
	
	# Generate filepath
	$fileroot = make_absolute(UWEH_FILES_PATH);
	$found = False;
	for ($i = 0; $i < UWEH_PREFIX_MAX_TRIES; $i++) {
		$subdir = gen_prefix();
		$filepath = $subdir . '/' . $filename;
		if ( $found = !file_exists($fileroot.$filepath) ) break;
	}
	if (!$found) throw new FilenameCollision($filename);

	# Save file
	$try_to_save_file = function () use ($fileroot, $subdir, $filepath, $file) : ?string {
		if (!file_exists($fileroot.$subdir)) {
			$dir_ok = @mkdir($fileroot.$subdir); # Suppress warnings
			if (!$dir_ok) return null;

			$move_ok = move_uploaded_file($file['tmp_name'], $fileroot.$filepath);
			if (!$move_ok) return null;

			return $filepath;
		}
	};

	$saved = $try_to_save_file();
	if (!is_null($saved)) return $saved;
	
	# Try again because there may be a race condition where the request is made at the same time
	# as the cleanup job (unlikely)  which deletes the empty directory after it is created,
	# but before the file is moved into it (rare).
	clearstatcache();
	$saved = $try_to_save_file();
	if (!is_null($saved)) return $saved;

	error_log("Uweh error: Failed to move ".$file['tmp_name']." to $fileroot$filepath");

	throw new SaveFail($filepath);
}

/** Get download URL from `save_file`'s filepath */
function get_download_url (string $filepath) : string {
	$encoded = implode("/", array_map("rawurlencode", explode("/", $filepath)));
	return UWEH_DOWNLOAD_URL.$encoded;
}

/** Try to run poor man's cron cleanup job based on the configuration
 * 
 * @return bool whether it ran the cleanup job
 */
function poor_mans_cron_cleanup () : bool {
	$ran_cleanup = False;
	if (POOR_MAN_CRON_INTERVAL) {
		$mydir = dirname(__FILE__);
		$previous = file_get_contents($mydir."/lastrun.txt");
		if (!$previous || time() - $previous >= POOR_MAN_CRON_INTERVAL * 60) {
			# Minimize race window for two scripts to run the cleanup at the same time
			file_put_contents($mydir."/lastrun.txt", time());
			exec("sh \"$mydir/clean_files.sh\"");
			$ran_cleanup = True;
		}
	}
	return $ran_cleanup;
}

/* Helper functions */

/** Check if the file has been uploaded successfully, is not empty, and doesn't exceed size limit
 * 
 * @param array $file Single file $_FILES array
 * @throws UploadError
 * @throws FileTooBig
 * @throws EmptyFile
 */
function check_file (array $file) : void {
	$error = $file['error'];
	if ($error !== 0 && $error !== UPLOAD_ERR_FORM_SIZE)
		throw new UploadError($error);
	if ($error === UPLOAD_ERR_FORM_SIZE || $file['size'] > UWEH_MAX_FILESIZE)
		throw new FileTooBig($file['size']);
	if ($file['size'] === 0)
		throw new EmptyFile();
}

/** Check if the filename's extension is allowed
 * 
 * @throws BadFileExtension
 */
function check_extension (string $filename) : void {
	# Distinguish between no extension (null) and an empty extension ("")
	$fileext = pathinfo($filename)['extension'] ?? $filename;
	if (!is_null($fileext) && !is_extension_allowed($fileext)) {
		throw new BadFileExtension($fileext);
	}
}

/** Returns if the file extension is allowed
 * 
 * We check the lowercased extension.
*/
function is_extension_allowed (string $ext) : bool {
	$ext = strtolower($ext);
	if (UWEH_EXTENSION_FILTERING_MODE === 'BLOCKLIST') {
		return !in_array($ext, UWEH_EXTENSION_BLOCKLIST);
	} else if (UWEH_EXTENSION_FILTERING_MODE === 'GRANTLIST') {
		return in_array($ext, UWEH_EXTENSION_GRANTLIST);
	} else if (UWEH_EXTENSION_FILTERING_MODE === 'NONE') {
		return True;
	} else { // Default to 'BLOCKLIST' behaviour
		return !in_array($ext, UWEH_EXTENSION_BLOCKLIST);
	}
}

/** Remove characters forbidden by the filesystem from the user's filename */
function sanitize_name (string $name) : string {
	return str_replace(["\0", "/", "\\"], "", $name);
}

/** Shorten the filename with heuristics on what to remove
 * 
 * In order, it tries to shorten:
 * - The string up to the first dot
 * - The string up to the last dot
 * - The whole string (in which case you should recheck the extension)
 * 
 * Calls the mbstring function if it is available, because we don't want to have invalid UTF-8 filenames
 * even if it is allowed.
 * 
 * @return array [$filename, $recheck_ext] The truncated filename, and if the extension might have changed
 */
function shorten_name (string $s) : array {
	$len = strlen($s);
	if ($len <= UWEH_LONGEST_FILENAME) {
		return [ $s, False ];
	}

	# Truncation function
	if (function_exists('mb_strcut')) {
		$strcut = function ($s, $pos) { return mb_strcut($s, 0, $pos, 'UTF-8'); };
	} else {
		$strcut = function ($s, $pos) { return substr($s, 0, $pos); };
	}

	var_dump($s);

	# If strpos returns false (thus strrpos as well), $i numifies to 0 so both ($cut > 0) will be false
	$i = strpos($s, '.');
	$cut = UWEH_LONGEST_FILENAME - ($len - $i); # prefix length
	if ($cut > 0) return [ strcut($s, $cut) . substr($s, $i), False ];

	$i = strrpos($s, '.');
	$cut = UWEH_LONGEST_FILENAME - ($len - $i);
	if ($cut > 0) return [ $strcut($s, $cut) . substr($s, $i), False ];

	var_dump($strcut("abc", 1));

	# Default to blindly cutting
	return [ $strcut($s, UWEH_LONGEST_FILENAME), True ];
}

/** Generates a valid random filename and tries to keep the original extension
 * 
 * In order, it:
 * - Keeps the extension after the first dot
 * - Keeps the extension after the last dot
 * - Drops the extension
 * 
 * If there is no original filename, it generates a random filename without an extension.
 * 
 * @param string $name The original filename
 */
function gen_random_name (string $orig = "") : string {
	$name = '';
	for ($i = 0; $i < UWEH_RANDOM_FILENAME_LENGTH; $i++) {
		$name .= PREFIX_ALPHABET[mt_rand(0, PREFIX_ALPHABET_LAST)];
	}

	$len = strlen($orig);
	$max_ext = UWEH_LONGEST_FILENAME - UWEH_RANDOM_FILENAME_LENGTH;

	$i = strpos($orig, '.');
	if (is_int($i)) { # The original filename contains a dot
		if ($len - $i <= $max_ext) return $name . substr($orig, $i);

		$i = strrpos($orig, '.');
		if ($len - $i <= $max_ext) return $name . substr($orig, $i);
	}
	return $name;
}

/** Generates a random prefix
 * 
 * The length is set by `PREFIX_LENGTH` and the alphabet depends
 * on `PREFIX_ALPHABET` and `PREFIX_ALPHABET_LAST`.
 */
function gen_prefix () : string {
	$name = '';
	for ($i = 0; $i < PREFIX_LENGTH; $i++) {
		$name .= PREFIX_ALPHABET[mt_rand(0, PREFIX_ALPHABET_LAST)];
	}
	return $name;
}

/** Turns a path relative to the repo root into an absolute path
 * 
 * @param string $p The path, relative or absolute
 */
function make_absolute (string $p) : string {
	if (strlen($p) && $p[0] !== '/') {
		return  dirname(__FILE__, 2) . "/" . $p;
	} else {
		return $p;
	}
}

/* Error handling */

/** Wraps UPLOAD_ERR_* excluding file size error
 * 
 * cf. <https://www.php.net/manual/en/features.file-upload.errors.php>
 */
class UploadError extends Exception {
	public /*int*/ $error_code;
	function __construct (int $error_code) {
		parent::__construct("File upload failed with error code $error_code");
		$this->error_code = $error_code;
	}
}

/** The uploaded file exceeds UWEH_MAX_FILESIZE */
class FileTooBig extends Exception {
	public /*int*/ $size; # In bytes
	function __construct (int $size) {
		parent::__construct("Uploaded file exceeds UWEH_MAX_FILESIZE");
		$this->size = $size;
	}
}

/** The uploaded file is empty */
class EmptyFile extends Exception {
	function __construct () {
		parent::__construct("Uploaded file cannot be empty");
	}
}

/** The uploaded file's extension is not allowed by Uweh */
class BadFileExtension extends Exception {
	public /*string*/ $extension;
	function __construct (string $extension) {
		parent::__construct("Uploaded file extension '$extension' is not allowed");
		$this->extension = $extension;
	}
}

/** Filename collided too many times */
class FilenameCollision extends Exception {
	public /*string*/ $filename;
	function __construct (string $filename) {
		parent::__construct("Filename '$filename' collided more than UWEH_PREFIX_MAX_TRIES times");
		$this->filename = $filename;
	}
}

/** The server failed to save the file */
class SaveFail extends Exception {
	public /*string*/ $path;
	function __construct (string $filepath) {
		parent::__construct("Failed to save file to '$filepath'");
		$this->path = $filepath;
	}
}

/* Utilities */

/** Tests if a $_FILES array is a single file (and not multiple) */
function is_single_file (array $file) : bool {
	return is_int($file['size']);
}

/** Abstraction over Uweh business exceptions */
class Error {
	const SOME_ERROR   = -1; # Generic error
	const BAD_FILE     = 1; # File too large
	const UPLOAD_FAIL  = 2; # No file uploaded (correctly)
	const SERVER_ERROR = 3; # Server error (IO generally)

	/** Categorize the exception into one of the Error constants */
	public function categorize (Exception $e) : int {
		if ($e instanceof UploadError) {
			switch ($e->error_code) {
				case UPLOAD_ERR_INI_SIZE:
				case UPLOAD_ERR_NO_TMP_DIR:
				case UPLOAD_ERR_CANT_WRITE:
				case UPLOAD_ERR_EXTENSION:
					return self::SERVER_ERROR; break;
				case UPLOAD_ERR_FORM_SIZE:
					return self::BAD_FILE; break;
				case UPLOAD_ERR_PARTIAL:
				case UPLOAD_ERR_NO_FILE:
					return self::UPLOAD_FAIL; break;
				default:
					return self::SOME_ERROR;
			}
		} else if ($e instanceof FileTooBig || $e instanceof EmptyFile || $e instanceof EmptyFile || $e instanceof BadFileExtension) {
			return self::BAD_FILE;
		} else if ($e instanceof FilenameCollision) {
			return self::SOME_ERROR;
		} else if ($e instanceof SaveFail) {
			return self::SERVER_ERROR;
		} else {
			return self::SOME_ERROR;
		}
	}
}

/** Convert a number of bytes into a human readable format (kB, GB, …)
 * 
 * Uses base 1024 and rounds down the result to 1 decimal place.
 */
function human_bytes (int $n) : string {
	$units = ["B", "kB", "MB", "GB", "TB"];
	for ($i = 0; $i < count($units); $i++) {
		if ($n < 1024) {
			break;
		} else {
			$n /= 1024;
		}
	}
	$n = floor($n * 10) / 10; # Truncate to 1 decimal place
	return $n . $units[$i];
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
