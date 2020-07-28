#!/bin/sh

# Pass an argument for the script to print OK and exit if the configuration is valid

cd "$(dirname "$0")" || exit;
cd ..;

FILE_ROOT=$( perl -ne 'print $1 if /UWEH_FILES_PATH"\s*,\s*"([^"]+)"/' src/config.php )
MIN_AGE=$( perl -ne 'print eval $1 if /UWEH_MAX_RETENTION_TIME"\s*,\s*([0-9_]+)/' src/config.php )
# The underscore in the character class allows for underscore in numbers, which is processed with perl's eval

if [ -z "$FILE_ROOT" ] || [ -z "$MIN_AGE" ]
then exit
fi

if [ ! -z "$1" ]
then
	echo "OK";
	echo "$FILE_ROOT"
	echo "$MIN_AGE"
	exit
fi

find "$FILE_ROOT" -mmin +"$MIN_AGE" -exec rm -f {} \;
