<?php
# At the very least, check the option values tagged with CHECKME

# URL of the homepage with protocol and trailing slash.
define("UWEH_MAIN_URL", "http://example.com/"); /* CHECKME */

# Abuse email for reported files
define("UWEH_ABUSE_EMAIL", "abuse@example.com"); /* CHECKME */

# Max retention time in minutes. It MUST be an integer because it is used by clean_files.sh
define("UWEH_MAX_RETENTION_TIME", 60);
# As a text string
define("UWEH_MAX_RETENTION_TEXT", "1 hour");

# URL root for downloads with protocol and trailing slash
define("UWEH_DOWNLOAD_URL", UWEH_MAIN_URL."files/"); /* CHECKME */

# Path to the uploaded file directory *with trailing slash*.
# It can be an absolute path, or a path relative to repository root directory.
# It MUST be a double quoted string without escapes (eg. \n) as it is used by clean_files.sh
# For example, if you write…     it means…
#   "public/files/"            →   /path/to/Uweh/public/files
#   "/absolute/path/to/files/" →   /absolute/path/to/files/
define("UWEH_FILES_PATH", "public/files/"); /* CHECKME */

# Max filesize in bytes (2MB is default from php.ini)
define("UWEH_MAX_FILESIZE", 2 * 1024 * 1024); /* CHECKME */

# Longest filename to save. We shorten other filenames to this limit.
define("UWEH_LONGEST_FILENAME", 200);
# Length of the randomly generated filenames (without the extension)
define("UWEH_RANDOM_FILENAME_LENGTH", 6);
# How many prefixes to generate before giving up
define("UWEH_PREFIX_MAX_TRIES", 7);

# Do you block a list of extensions (BLOCKLIST) or only allow a list of extensions (GRANTLIST) ?
# Set to NONE if you want to disable filtering
define("UWEH_EXTENSION_FILTERING_MODE", "BLOCKLIST");
# Each extension should be lowercase. Note that '.htaccess' has an extension of 'htaccess'.
define("UWEH_EXTENSION_BLOCKLIST", array("exe", "scr", "rar", "zip", "com", "vbs", "bat", "cmd", "html", "htm", "msi", "php", "php5", "htaccess"));
define("UWEH_EXTENSION_GRANTLIST", array("txt", "pdf"));

# If you can't run the cleanup script with a cron job, set this to the
# cleanup interval in minutes. 0 means disable.
# It is much preferable to use an actual cron job
define("POOR_MAN_CRON_INTERVAL", 0);

# Finally, you could customize the constants in Uweh.php at your risks and perils.
