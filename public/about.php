<?php

require_once '../src/UwehTpl.php';
require_once '../src/Uweh.php';

$d = [
	'page' => 'about',

	# Page footer
	'version' => Uweh\VERSION,
	'cleanup-func' => function () { Uweh\poor_mans_cron_cleanup(); },

	# Page header
	'humanMaxFilesize' => Uweh\human_bytes(UWEH_MAX_FILESIZE),
	'maxRetentionText' => UWEH_MAX_RETENTION_TEXT,
	"mainUrl" => UWEH_MAIN_URL,

	# Html head
	'title' => 'Uweh - About',
	#| NB Copied from index.php
	'description' => "Temporary file hosting. Share files up to " . Uweh\human_bytes(UWEH_MAX_FILESIZE) . " for " . UWEH_MAX_RETENTION_TEXT . ".",
	'canonical' => UWEH_MAIN_URL.'about.php',
	"favicon-32" => UWEH_MAIN_URL.'favicon-32.png',
	"favicon-16" => UWEH_MAIN_URL.'favicon-16.png',
	"favicon-196" => UWEH_MAIN_URL.'favicon-196.png',
	"og:image" => UWEH_MAIN_URL."riamu.png",

	# About text
	'abuseEmail' =>  UWEH_ABUSE_EMAIL,
];

UwehTpl\php_html_header($d);

UwehTpl\html_start('body');
UwehTpl\html_start('main');

	UwehTpl\body_header($d);
	UwehTpl\about_text($d);

UwehTpl\html_end('main');

UwehTpl\riamu_picture($d);
UwehTpl\page_javascript($d);
UwehTpl\generation_line($d);

UwehTpl\html_end('body');
UwehTpl\html_end('html');
