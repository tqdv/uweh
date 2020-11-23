#!/bin/sh

USER="$1"
GROUP="$2"

if [ -z "$1" ] || [ -z "$2" ]
then
	echo "Missing arguments. Usage: $0 <user> <group>"
	exit 1
fi

echo "Setting owners"

C="chown $USER:$GROUP public public/* src src/"
echo "$ $C"
$C

echo "Setting permissions"

chmod g=rx,o=rx public
chmod g=r,o=r public/*

chmod g=rwx,o=rx src
chmod g=r,o=r src/*
chmod g=rx src/clean_files.sh
chmod g=rw src/lastrun.txt
