#!/bin/sh
# 
# clean_files.sh - Delete expired files from Uweh
# 
# === Synopsis ===
# 
#   ```sh
#   # Call with no arguments to delete expired files based on config.php
#   ./clean_files.sh
#   
#   # Call with any argument to check config.php:
#   ./clean_files.sh test
#   ```
# 
# === Description
# 
# We grab the necessary information from the configuration file using perl regexes,
# and then use `find` to delete expired files and empty folders.
# 
# We only check that the configuration variables are non empty.
# To make sure they are correct, run this script with an argument eg. `./clean_files.sh test`
# which will display the loaded configuration
# 
# === Exit codes ===
# 
# * 0: Success
# * 1: Error before file deletion
# * 2: Error after file deletion
# 
# === Misc ===
# 
# Linted with <https://www.shellcheck.net/>

# Change directory to repo root
cd "$(dirname "$0")" && cd .. || exit 1

# Check config file
CONFIG_FILE="src/config.php"
if ! { [ -f "$CONFIG_FILE" ] && [ -r "$CONFIG_FILE" ]; }
then
	echo "Configuration file can't be read";
	exit 1;
fi

# Grab relevant variables from src/config.php
FILE_ROOT=$( perl -ne 'print $1 if /UWEH_FILES_PATH"\s*,\s*"([^"]+)"/' "$CONFIG_FILE" )
MIN_AGE=$( perl -ne 'print eval $1 if /UWEH_MAX_RETENTION_TIME"\s*,\s*([0-9_]+)/' "$CONFIG_FILE" )
# ^ The underscore in the character class allows for underscores in PHP number literals,
#   which is then processed correctly by perl's eval

# Test config and exit if we were called with an argument
if [ -n "$1" ]; then
	echo "=== Configuration and Status ==="
	echo "Script working directory: $(pwd)"
	echo "File directory: $FILE_ROOT"
	echo "Maximum file age (in minutes): $MIN_AGE"
	exit 0
fi

# Check configuration variables
if ! { [ -z "$FILE_ROOT" ] || [ -z "$MIN_AGE" ]; } {
	echo "Couldn't find configuration in configuration file";
	exit 1;
}

# Delete files and empty folders
find "$FILE_ROOT" -mindepth 1 -type f -mmin +"$MIN_AGE" -exec rm -f '{}' \; || exit 2
find "$FILE_ROOT" -mindepth 1 -maxdepth 1 -empty -type d -exec rmdir '{}' \; || exit 2

exit 0
