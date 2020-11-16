<?php
# You should at the very least properly set UWEH_MAIN_URL and UWEH_ABUSE_EMAIL
# 
# See documentation at the end of the file. It is split from the actual configuration for clarity

# Urls
define("UWEH_MAIN_URL", "http://example.com/");
define("UWEH_DOWNLOAD_URL", UWEH_MAIN_URL."files/");

# Website content
define("UWEH_ABUSE_EMAIL", "abuse@example.com");
define("UWEH_MAX_RETENTION_TEXT", "1 hour");

# File storage
define("UWEH_FILES_PATH", "public/files/"); # used in cleanup script
define("UWEH_MAX_FILESIZE", 2 * 1024 * 1024 /* bytes */);
define("UWEH_MAX_RETENTION_TIME", 60 /* minutes */); # used in cleanup script

# Filepath options
define("UWEH_LONGEST_FILENAME", 200);
define("UWEH_RANDOM_FILENAME_LENGTH", 6);
define("UWEH_PREFIX_MAX_TRIES", 7);

# Extension filtering
define("UWEH_EXTENSION_FILTERING_MODE", "BLOCKLIST");
define("UWEH_EXTENSION_BLOCKLIST", array("exe", "scr", "rar", "zip", "com", "vbs", "bat", "cmd", "html", "htm", "msi", "php", "php5", "htaccess"));
define("UWEH_EXTENSION_GRANTLIST", array("txt", "pdf"));

# File cleanup method
define("POOR_MAN_CRON_INTERVAL", 0);


# === Configuration options ===
# 
# UWEH_MAIN_URL (string): The main url with protocol (eg. `https://`) and trailing slash.
#     This is the url used to access the main page.
#     Example: "https://uweh.ga/"
# 
# UWEH_DOWNLOAD_URL (string): The download url prefix with protocol and trailing slash.
#     This is the prefix for all download urls.
#     Examples: UWEH_MAIN_URL.'files/'
#               "https://f.uweh.ga/"
#
# UWEH_ABUSE_EMAIL (string): The abuse email address displayed on the about page.
#     Example: "abuse@uweh.ga"
#
# UWEH_MAX_RETENTION_TEXT (string): The text string representing the maximum retention time displayed to the user.
#     It should match the value in UWEH_MAX_RETENTION_TIME
#     Example: "1 hour"
#
# UWEH_FILES_PATH (string): The file storage directory relative to the repo root or an absolute path, with a trailing slash.
#     MUST be a literal double quoted string
#     Examples: "public/files/"
#                "/srv/uweh/"
#
# UWEH_MAX_FILESIZE (int): Maximum filesize in bytes, should be positive.
#     Example: 2 * 1024 * 1024
#
# UWEH_MAX_RETENTION_TIME (int): Maximum file retention time in minutes.
#     MUST be a literal integer
#     Example: 60
#
# UWEH_LONGEST_FILENAME (int): Longest filename length. Longer names will be truncated.
#     This should be less than 255 for filesystem reasons.
#     Example: 200
#
# UWEH_RANDOM_FILENAME_LENGTH (int): Length of the randomly generated filenames.
#     Example: 6
#
# UWEH_PREFIX_MAX_TRIES (int): How many times to regenerate a new storage path if it already exists. Should be positive.
#     Lower means you're more likely to reject a file with the same name as one already stored on the server.
#     Example: 7
#
# UWEH_EXTENSION_FILTERING_MODE (string): What extension filtering mode to use.
#     Either BLOCKLIST, GRANTLIST or NONE
#     Example: "BLOCKLIST"
#
# UWEH_EXTENSION_BLOCKLIST (array of string): The list of blocked extensions in blocklist mode.
#     Example: array("exe", "html", "php")
#
# UWEH_EXTENSION_GRANTLIST (array of string): The list of allowed extensions in grantlist mode.
#     Example: array("txt", "pdf")
#
# POOR_MAN_CRON_INTERVAL (int): Number of minutes between cleanups when using poor man's cron.
#     Set it to 0 to disable (recommended)
#     Example: 60

# Also, you could customize the constants in Uweh.php at your risks and perils.
