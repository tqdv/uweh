#!/bin/sh

# set_permissions.sh - Set file permissions and ownership on Uweh files
#
# === Synopsis ===
#
#   # Set files permissions, and set ownership to user julio, with group www-data
#   ./bin/set_permissions julio www-data
#
# === Description ===
# 
# This sets (my) ideal file permissions: read-only unless writing is needed.
# It assumes that the webserver is not owner of the source files,
# but that belongs to the group supplied on the command line, usually typically www-data.
#
# === Misc ===
#
# Linted with <https://www.shellcheck.net/>

USER="$1"
GROUP="$2"

if [ -z "$1" ] || [ -z "$2" ]
then
	echo "Missing arguments. Usage: $0 <user> <group>"
	exit 1
fi

echo "Setting ownership to $USER:$GROUP"

chown $USER:$GROUP public public/* src src/

echo "Setting permissions. Ignore warnings for files that don't exist"

chmod g=rx,o=rx public
chmod g=r,o=r public/favicon-*.png public/*.php public/*.css

chmod g=rwx,o=rx src
chmod g=r,o=r src/*
chmod g+x src/clean_files.sh
chmod g+w src/lastrun.txt
