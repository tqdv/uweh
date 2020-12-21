#!/usr/bin/env perl

# add_tracking_include.pl - Make Uweh insert src/tracking.html at the end of the html head
#
# === Synopsis ===
#
#   # Copy the tracking html snippet to src/tracking.html and then run the following command:
#   ./bin/add_tracking_include.pl
#
# === Description ===
# 
# This script sets $d['endofhead'] (in UwehTpl) to the contents of tracking.html by editing the php files in place.

use v5.20;

for my $filebase (qw< index about upload >) {
	my $file = "public/${filebase}.php";

	# Read the file
	my $data;
	{
		open my $fh, '<', $file or die "Failed to open file $file: $!";
		local $/ = undef;
		$data = <$fh>;
		close $fh or die "Unable to close $file: $!";
	}

	# Edit it
	my $anchor = 'UwehTpl\php_html_header($d);';

	my $php = <<'END';
$d['endofhead'] = @file_get_contents(dirname(__FILE__, 2) . '/src/tracking.html'); /* add_tracking_include.pl */
END
	$data =~ s/(\Q$anchor\E)/$php$1/;

	# Write it back
	{
		open my $fh, '>', $file or die "Failed to open file $file: $!";
		print { $fh } $data;
		close $fh or die "Unable to close $file: $!";
	}
}

say "Done";