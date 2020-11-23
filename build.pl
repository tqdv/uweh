#!/usr/bin/env perl

use v5.20;
use File::Spec;
use File::Path qw(make_path remove_tree);

# === Build configuration
my @files = qw<
	src/clean_files.sh
	src/Uweh.php
	src/config.template.php

	public/index.php
	public/api.php
	public/status.php
	
	public/main.css

	bin/set_permissions.sh

	README.md
	LICENSE
	CHANGELOG.md
>;

my @custom_files = qw<
	src/config.php

	public/favicon-32.png
	public/favicon-16.png
	public/favicon-196.png
	public/riamu.png
>;

my $build_dir = "build";
my $date = `date +%F_%H-%M-%S`; chomp $date;
my $output_tar = "$build_dir/uweh-$date.tar.gz";
my $custom_output_tar = "$build_dir/uweh-mine-$date.tar.gz";
# ...

my $want_help = grep { /^(?: -h | --help )/x } @ARGV;
my $no_args = @ARGV == 0;

if ($want_help) {
	print <<~END;
		Usage: $0 <subcommand>
		       $0 --help | -h

		Subcommands:
		  start    Build a tarball
		  clean    Remove all previous builds
		END
	exit 0;
}

if ($no_args) {
	print <<~END;
		Usage: ./build.pl start
		Help:  ./build.pl --help
		END
	exit 0;
}

my $command = shift @ARGV;

if ($command eq 'start') {
	make_path($build_dir);

	my $c = "tar czf $output_tar @files";
	print `echo Running: $c`;
	`$c`;

	if ($?) {
		say "Build failed";
		exit -1;
	}

} elsif ($command eq 'mine') {
	make_path($build_dir);

	my $c = "tar czf $custom_output_tar --ignore-failed-read @files @custom_files";
	`$c`;

	if ($?) {
		say "Build failed";
		exit -1;
	}

} elsif ($command eq 'clean') {
	remove_tree($build_dir, { keep_root => 1 });

} else {
	say "Unknown subcommand $command, see $0 --help";
}
