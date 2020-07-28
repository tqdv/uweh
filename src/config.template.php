<?php
// At the very least, check the option values tagged with CHECKME

# URL of the homepage with protocol and trailing slash.
define("UWEH_MAIN_URL", "http://example.com/"); /* CHECKME */

# Max retention time in minutes
# MUST be an integer
define("UWEH_MAX_RETENTION_TIME", 60);
# As a text string
define("UWEH_MAX_RETENTION_TEXT", "1 hour");

# URL root for downloads with protocol and trailing slash
define("UWEH_DOWNLOAD_URL", UWEH_MAIN_URL."files/"); /* CHECKME */

# Path to the uploaded file directory *with trailing slash*. It can be either
# absolute, or relative to code repository root directory.
# MUST be a double quoted string without escapes (eg. \n)
# Examples:
#   "public/files/"           → /path/to/Uweh/public/files
#   "/absolute/path/to/files" → /absolute/path/to/files
define("UWEH_FILES_PATH", "public/files/");

# Max filesize in bytes (2MB is default from php.ini)
define("UWEH_MAX_FILESIZE", 2 * 1024 * 1024); /* CHECKME */

# Longest filename length and random prefix length
define("UWEH_LONGEST_FILENAME", 200);
define("UWEH_PREFIX_LENGTH", 12);
# UWEH_LONGEST_FILENAME + UWEH_PREFIX_LENGTH should be ≤ 254
# It's 254 and not 255 because we add an underscore

# Do you block a list of extensions (BLOCKLIST)
# or only allow a list of extensions (GRANTLIST) ?
# Set to NONE if you want to disable filtering
define("UWEH_EXTENSION_FILTERING_MODE", "BLOCKLIST");
# Each extension should be lowercase
define("UWEH_EXTENSION_BLOCKLIST", array("exe", "scr", "rar", "zip", "com", "vbs", "bat", "cmd", "html", "htm", "msi", "php", "php5"));
define("UWEH_EXTENSION_GRANTLIST", array("txt", "pdf"));

# If you can't run the cleanup script with a cron job, set this to the
# cleanup interval in minutes. 0 means disable.
# It is much preferable to use an actual cron job though
define("POOR_MAN_CRON_INTERVAL", 0);
