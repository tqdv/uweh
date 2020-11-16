<?php

# Takes a shorthand byte value and returns the number of bytes
# cf. https://www.php.net/manual/en/faq.using.php#faq.using.shorthandbytes
function shorthand_to_bytes ($x) {
	# Wonky parsing, won't fix
	$value = strpos($x, '.') === false ? intval($x) : floatval($x);
	
	switch ($x[-1]) {
	case 'k':
	case 'K':
		return $value * 1024;
		break;
	case 'm':
	case 'M':
		return $value * 1024 * 1024;
		break;
	case 'g':
	case 'G':
		return $value * 1024 * 1024 * 1024;
		break;
	default:
		return $value;
	}
}

# Display an HTML table row of `| $desc | $data (with color based on $status) |`
function row (string $desc, $data, $status = null) {
	if (is_bool($status)) {
		$status = $status ? "ok" : "fail";
	}
	
	if (is_bool($data)) {
		if (is_null($status)) {
			$status = $data ? "ok" : "fail";
		}
		$data = $data ? "OK" : "FAIL";
	}
	
	echo "<tr>";
	echo '<td>'.htmlspecialchars($desc).'</td>';
	echo "<td class=\"$status\">".$data."</td>";
	echo "</tr>";
}

# Display the table section header of name $name
function section (string $name) {
	echo "<th colspan=2>$name</th>";
}

function exit_unless (bool $cond) {
	if (!$cond) exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Uweh status</title>
	<style>
		/* Table style */
		table {
			margin: auto;
		}
		th,td {
			white-space: pre-line;
			text-align: center;
		}
		
		/* Cell colors */
		.ok {
			background-color: #8eec6c;
		}
		.fail {
			background-color: #ff8f8f;
		}
		.meh {
			background-color: #ffc253;
		}

		h1 {
			text-align: center;
		}
		.warning {
			font-size: xx-large;
			text-align: center;
		}
	</style>
</head>
<body>

<h1>Configuration checklist</h1>

<table>
<tr>
	<th scope="col">Variable</th>
	<th scop="col">Value</th>
</tr>
<!-- Table contents -->
<?php

# ---
section("PHP");

$valid_php = version_compare(PHP_VERSION, "7.1", ">=");
row("PHP_VERSION ≥ 7.1", PHP_VERSION, $valid_php);
exit_unless($valid_php);

# ---
section("Files");

$included_config = (bool) include_once '../src/config.php';
row("Configuration file exists", "../src/config.php", $included_config);
exit_unless($included_config);

$included_uweh = (bool) include_once '../src/Uweh.php';
row("Uweh php lib exists", "../src/Uweh.php", $included_uweh);
exit_unless($included_uweh);

row("Cleanup script exists", "../src/clean_files.sh", file_exists(dirname(__FILE__, 2) . "/src/clean_files.sh"));

# --- 
section("Configuration");

$configs = array("UWEH_MAIN_URL", "UWEH_ABUSE_EMAIL", "UWEH_MAX_RETENTION_TIME", "UWEH_MAX_RETENTION_TEXT", "UWEH_DOWNLOAD_URL", "UWEH_FILES_PATH", "UWEH_MAX_FILESIZE", "UWEH_LONGEST_FILENAME", "UWEH_RANDOM_FILENAME_LENGTH", "UWEH_PREFIX_MAX_TRIES", "UWEH_EXTENSION_FILTERING_MODE", "UWEH_EXTENSION_BLOCKLIST", "UWEH_EXTENSION_GRANTLIST", "POOR_MAN_CRON_INTERVAL");
$missing_conf = array();
foreach ($configs as $conf) {
	if (is_null(constant($conf))) {
		array_push($missing_conf, $conf);
	}
}
$no_missing_conf = ! count($missing_conf);
$missing_conf_msg = $no_missing_conf ? "OK" : "Missing: ".implode(", ", $missing_conf);
row("Configuration constants are defined", $missing_conf_msg , $no_missing_conf);
exit_unless($no_missing_conf);

# Check configuration values

row("UWEH_MAIN_URL has protocol and trailing slash", UWEH_MAIN_URL, UWEH_MAIN_URL[-1] === '/' && strpos(UWEH_MAIN_URL, '://') !== False);

row("UWEH_ABUSE_EMAIL is valid", UWEH_ABUSE_EMAIL, strpos(UWEH_ABUSE_EMAIL, '@') !== False ? "meh" : "fail" );

row("UWEH_MAX_RETENTION_TIME is a positive integer", UWEH_MAX_RETENTION_TIME, is_int(UWEH_MAX_RETENTION_TIME) && UWEH_MAX_RETENTION_TIME > 0);

row("UWEH_MAX_RETENTION_TEXT corresponds to UWEH_MAX_RETENTION_TIME in minutes", UWEH_MAX_RETENTION_TEXT, "meh");

row("UWEH_DOWNLOAD_URL has protocol and trailing slash", UWEH_DOWNLOAD_URL, UWEH_DOWNLOAD_URL[-1] === '/' && strpos(UWEH_DOWNLOAD_URL, '://') !== False);

$valid_files_dir = UWEH_FILES_PATH[-1] === '/';
row("UWEH_FILES_PATH has trailing slash", UWEH_FILES_PATH, $valid_files_dir);
exit_unless($valid_files_dir);

$valid_max_filesize = is_int(UWEH_MAX_FILESIZE) && UWEH_MAX_FILESIZE > 0;
if ($valid_max_filesize && UWEH_MAX_FILESIZE < 300*1000) $valid_max_filesize = "meh";
row("UWEH_MAX_FILESIZE is a positive integer\n(recommended > 300kB)", UWEH_MAX_FILESIZE." (".Uweh\human_bytes(UWEH_MAX_FILESIZE).")", $valid_max_filesize);

$valid_longest_filename = is_int(UWEH_LONGEST_FILENAME) && UWEH_LONGEST_FILENAME > 0 && UWEH_LONGEST_FILENAME <= 255;
row("UWEH_LONGEST_FILENAME is a positive integer ≤ 255", UWEH_LONGEST_FILENAME, $valid_longest_filename);

$valid_rand_fn_len = is_int(UWEH_RANDOM_FILENAME_LENGTH) && UWEH_RANDOM_FILENAME_LENGTH > 0;
row("UWEH_RANDOM_FILENAME_LENGTH is a positive integer", UWEH_RANDOM_FILENAME_LENGTH, $valid_rand_fn_len);

$valid_prefix_max_tries = is_int(UWEH_PREFIX_MAX_TRIES) && UWEH_PREFIX_MAX_TRIES > 1;
if ($valid_prefix_max_tries && UWEH_PREFIX_MAX_TRIES < 3) $valid_prefix_max_tries = "meh";
row("UWEH_PREFIX_MAX_TRIES is a positive integer ≥ 1\n(recommended ≥ 3)", UWEH_PREFIX_MAX_TRIES, $valid_prefix_max_tries);

$known_filter_mode = in_array(UWEH_EXTENSION_FILTERING_MODE, array('NONE', 'BLOCKLIST', 'GRANTLIST'));
row("UWEH_EXTENSION_FILTERING_MODE is known", UWEH_EXTENSION_FILTERING_MODE, $known_filter_mode);

row("UWEH_EXTENSION_BLOCKLIST is an array", implode(", ", UWEH_EXTENSION_BLOCKLIST), is_array(UWEH_EXTENSION_BLOCKLIST));
row("UWEH_EXTENSION_GRANTLIST is an array", implode(", ", UWEH_EXTENSION_GRANTLIST), is_array(UWEH_EXTENSION_GRANTLIST));

row("POOR_MAN_CRON_INTERVAL is a positive integer", POOR_MAN_CRON_INTERVAL, is_int(POOR_MAN_CRON_INTERVAL) && POOR_MAN_CRON_INTERVAL >= 0);

# ---
section("Configuration coherence");

$ini_max_size = ini_get('upload_max_filesize');
$max_size = shorthand_to_bytes($ini_max_size);
row("upload_max_filesize (php.ini) ≥ UWEH_MAX_FILESIZE", "$ini_max_size (php.ini)", UWEH_MAX_FILESIZE <= $max_size);

$file_limit_msg = "Probably";
if (UWEH_MAX_FILESIZE > 1024 * 1024) $file_limit_msg = "To check manually";
row("Web server file limits are configured correctly", $file_limit_msg, "meh");

row("UWEH_RANDOM_FILENAME_LENGTH ≤ UWEH_LONGEST_FILENAME", UWEH_RANDOM_FILENAME_LENGTH <= UWEH_LONGEST_FILENAME);

row("POOR_MAN_CRON_INTERVAL is disabled", POOR_MAN_CRON_INTERVAL == 0 ?: "it's preferable to use a cron job", POOR_MAN_CRON_INTERVAL ? "meh" : "ok");

# ---
section("Filesystem");

$files_dir = Uweh\make_absolute(UWEH_FILES_PATH);
row("Files directory exists", $files_dir, $files_dir_exists = is_dir($files_dir));
exit_unless($files_dir_exists);

$test_file = $files_dir.'.keep';
row("Files directory is writable", file_put_contents($test_file, time()) !== False);
unlink($test_file);

$src_test_file = dirname(__FILE__, 2) . '/src/.test_write';
row("Source directory is writable", touch($src_test_file) !== False);
unlink($src_test_file);

# ---
section("Cleanup script");

$script_info = array();
$rootdir = dirname(__FILE__, 2);
exec("sh \"$rootdir/src/clean_files.sh\" status", $script_info, $retval);

row("Script runs test successfully", $retval === 0);

$script_file_path = explode(": ", $script_info[2])[1] ?? False;
row("Script has right files path", $script_file_path, $script_file_path === UWEH_FILES_PATH);

$script_time = explode(": ", $script_info[3])[1] ?? False;
row("Script has right time", $script_time, $script_time === (string) UWEH_MAX_RETENTION_TIME);

# Test script by creating files and seeing if they're deleted
$del_fn = $files_dir.'.to_delete';
$keep_fn = $files_dir.'.to_keep';

$created = touch($del_fn, time() - 60 * (UWEH_MAX_RETENTION_TIME+1));
touch($keep_fn);
exec("sh \"$rootdir/src/clean_files.sh\"", $output, $retval);
clearstatcache();
$deleted = !file_exists($del_fn);
$kept = file_exists($keep_fn);
row("Script deletes old files but keeps recent ones", $created && $retval === 0 && $deleted && $kept);
unlink($keep_fn);

# ---
section("Collision info");

$alphabet_size = strlen(Uweh\PREFIX_ALPHABET);
row("Prefix alphabet size", $alphabet_size, $alphabet_size == Uweh\PREFIX_ALPHABET_LAST + 1);
row("Subfolder name length", Uweh\PREFIX_LENGTH, Uweh\PREFIX_LENGTH > 0);
$subfolder_count = $alphabet_size**Uweh\PREFIX_LENGTH;
row("Unique subfolder/prefix names\n(recommended ≤ 10_000)", $subfolder_count, $subfolder_count <= 10_000);

$max_files = floor($subfolder_count * (1/10_000)**(1/UWEH_PREFIX_MAX_TRIES));
row("Max similar files so that Prob(generated invalid prefixes ≥ UWEH_PREFIX_MAX_TRIES) ≤ 1/10_000", $max_files, $max_files > 100);

$rand_max = $alphabet_size ** UWEH_RANDOM_FILENAME_LENGTH;
row("Unique random names", $rand_max, $rand_max > 100);

?>
<!-- End of table -->
</table>

<p class="warning">
	This file should be deleted or disabled as it contains sensitive information<br>
	You could rename it to status.txt
<p>

</body>
</html>
