#!/usr/bin/env perl

use v5.20;

my $userpw = shift @ARGV;
unless (defined($userpw)) {
	say "Missing argument. Usage: $0 <password>";
	exit;
}

# Generate hash and salt
my @chars = ('.', '/', '0'..'9', 'A'..'Z', 'a'..'z');
my $salt; $salt .= $chars[rand @chars] for 1..16;
my $encrypted; $encrypted .= $chars[rand @chars] for 1..43;
my $crypt_opt = '$5$' . $salt . '$' . $encrypted;

my $hash = crypt($userpw, $crypt_opt);

# Interpolate it into the php snippet
$hash =~ s|\$|\\\$|g;
my $php_code = <<'END';
<?php
if (!isset($hash)) $hash = "%hash%";
END
$php_code =~ s|%(\w+)%|"\$$1"|gee;

# Read the file
my $file = 'public/status.php';
my $data;
{
	open my $fh, '<', $file or die $!;
	local $/ = undef;
	$data = <$fh>;
	close $fh;
}

# Edit it
$data =~ s/^<\?php\s*//;
$data = $php_code . $data;

# Write it back
{
	open(my $fh, '>', $file) or die $!;
	print $fh $data;
	close $fh;
}

say "Done. Go to `status.php?do=auth` to login";
