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

function section (string $name) {
	echo "<th colspan=2>$name</th>";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Uweh status</title>
	<style>
	table {
		margin: auto;
	}
	th,td {
		white-space: pre-line;
		text-align: center;
	}
	
	h1 {
		text-align: center;
	}
	
	.ok {
		background-color: #8eec6c;
	}
	.fail {
		background-color: #ff8f8f;
	}
	.meh {
		background-color: #ffc253;
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
<?php

# Check files
section("Files");

$included_config = (bool) include_once '../src/config.php';
row("Configuration file exists", "../src/config.php", $included_config);
if (!$included_config) exit;

$included_uweh = (bool) include_once '../src/Uweh.php';
row("Uweh php lib exists", "../src/Uweh.php", $included_uweh);
if (!$included_uweh) exit;

row("Cleanup script exists", "../src/clean_files.sh", file_exists(dirname(__FILE__, 2) . "/src/clean_files.sh"));

# Check variable names
section("Configuration");

$configs = array("UWEH_MAIN_URL", "UWEH_MAX_RETENTION_TIME", "UWEH_MAX_RETENTION_TEXT", "UWEH_DOWNLOAD_URL", "UWEH_FILES_PATH", "UWEH_MAX_FILESIZE", "UWEH_LONGEST_FILENAME", "UWEH_PREFIX_LENGTH", "UWEH_EXTENSION_FILTERING_MODE", "UWEH_EXTENSION_BLOCKLIST", "UWEH_EXTENSION_GRANTLIST", "POOR_MAN_CRON_INTERVAL");
$missing_conf = array();
foreach ($configs as $conf) {
	if (is_null(constant($conf))) {
		array_push($missing_conf, $conf);
	}
}

$no_missing_conf = ! count($missing_conf);
row("Config constants are defined", $no_missing_conf ?: "Missing: ".implode(", ", $missing_conf) , $no_missing_conf);
if (!$no_missing_conf) exit;

# Check configuration values

row("Main url has trailing slash", UWEH_MAIN_URL, UWEH_MAIN_URL[-1] === '/');

row("Max retention time is a positive integer\n(in minutes)", UWEH_MAX_RETENTION_TIME, is_int(UWEH_MAX_RETENTION_TIME) && UWEH_MAX_RETENTION_TIME > 0);

row("Download url has trailing slash", UWEH_DOWNLOAD_URL, UWEH_DOWNLOAD_URL[-1] === '/');

row("Files path has trailing slash", UWEH_FILES_PATH, UWEH_FILES_PATH[-1] === '/');

row("UWEH_MAX_FILESIZE is a positive integer\n(in bytes)", UWEH_MAX_FILESIZE, is_int(UWEH_MAX_FILESIZE) && UWEH_MAX_FILESIZE > 0);

$ini_max_size = ini_get('upload_max_filesize');
$max_size = shorthand_to_bytes($ini_max_size);
row("upload_max_filesize ≥ UWEH_MAX_FILESIZE", "$ini_max_size (php.ini)", UWEH_MAX_FILESIZE <= $max_size);

row("UWEH_LONGEST_FILENAME is a positive integer", UWEH_LONGEST_FILENAME, is_int(UWEH_LONGEST_FILENAME) && UWEH_LONGEST_FILENAME >= 0);
row("UWEH_PREFIX_LENGTH is a positive integer", UWEH_PREFIX_LENGTH, is_int(UWEH_PREFIX_LENGTH) && UWEH_PREFIX_LENGTH >= 0);
$filename_length = UWEH_LONGEST_FILENAME + UWEH_PREFIX_LENGTH;
row("UWEH_LONGEST_FILENAME + UWEH_PREFIX_LENGTH ≤ 254", $filename_length, $filename_length <= 254);

$known_filter_mode = in_array(UWEH_EXTENSION_FILTERING_MODE, array('NONE', 'BLOCKLIST', 'GRANTLIST'));
row("UWEH_EXTENSION_FILTERING_MODE is known", UWEH_EXTENSION_FILTERING_MODE, $known_filter_mode);

row("UWEH_EXTENSION_BLOCKLIST is an array", implode(", ", UWEH_EXTENSION_BLOCKLIST), is_array(UWEH_EXTENSION_BLOCKLIST));
row("UWEH_EXTENSION_GRANTLIST is an array", implode(", ", UWEH_EXTENSION_GRANTLIST), is_array(UWEH_EXTENSION_GRANTLIST));

row("POOR_MAN_CRON_INTERVAL is a positive integer", POOR_MAN_CRON_INTERVAL, is_int(POOR_MAN_CRON_INTERVAL) && POOR_MAN_CRON_INTERVAL >= 0);
row("POOR_MAN_CRON_INTERVAL is disabled", POOR_MAN_CRON_INTERVAL == 0 ?: "it's preferable to use a cron job", POOR_MAN_CRON_INTERVAL ? "meh" : "ok");

# Script status
section("Cleanup script");

$script_info = array();
$rootdir = dirname(__FILE__, 2);
exec("sh \"$rootdir/src/clean_files.sh\" status", $script_info);

row("Script is valid", $script_info[0] === "OK");
row("Script has right files path", $script_info[1], $script_info[1] === UWEH_FILES_PATH);
row("Script has right time", $script_info[2], $script_info[2] === (string) UWEH_MAX_RETENTION_TIME);

# Check file system and access
section("Filesystem");

$files_dir = Uweh\make_absolute(UWEH_FILES_PATH);
row("Files directory exists", $files_dir, is_dir($files_dir));

$test_file = $files_dir.'/.keep';
row("Files directory is writable", file_put_contents($test_file, time()) !== False);
unlink($test_file);



# TODO check if folder exists

?>
</table>

<p class="warning">
	This file should be deleted or disabled as it contains sensitive information<br>
	You could rename it to status.txt
<p>

</body>
</html>