#!/usr/bin/env perl

# protect_status.pl - Set password for status.php
# 
# === Synopsis ===
#
#   # The command should be run from Uweh's root folder
#   
#   # Set status.php's password to 1234
#   ./bin/protect_status.pl 1234
#
# === Description ===
#
# This script password protects the status.php page by generating a crypt-compatible
# SHA256 hash of the supplied password, and inserting it into the PHP source.
# It should be run from Uweh's root folder.
#
# === Execution overview ===
#
# The password hash is generated using Perl's `crypt` function with a random salt (from Perl's `rand`).
# We edit status.php and assign the hash string to PHP's $hash variable.
# The PHP script will then check it using `password_verify` which calls PHP's `crypt`.
#
# === Misc ===
#
# Because the password is supplied on the command line, it is present in the shell history
# Linted with <http://perlcritic.com/>

use v5.20;

my $userpw = shift @ARGV;
unless (defined $userpw) {
	say "Missing argument. Usage: $0 <password>";
	exit;
}

# Generate hash and salt
my @chars = ('.', '/', '0'..'9', 'A'..'Z', 'a'..'z');
my $salt; $salt .= $chars[rand @chars] for 1..16;
my $encrypted; $encrypted .= $chars[rand @chars] for 1..43;
my $crypt_opt = '$5$' . $salt . '$' . $encrypted;

my $hash = crypt $userpw, $crypt_opt;

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
	open my $fh, '<', $file or die "Failed to open file $file: $!";
	local $/ = undef;
	$data = <$fh>;
	close $fh or die "Unable to close $file: $!";
}

# Edit it
$data =~ s/^<\?php\s*//;
$data = $php_code . $data;

# Write it back
{
	open my $fh, '>', $file or die "Failed to open file $file: $!";
	print { $fh } $data;
	close $fh or die "Unable to close $file: $!";
}

say "Done. Go to `status.php?do=auth` to login";
