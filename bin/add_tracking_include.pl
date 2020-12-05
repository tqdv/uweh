#!/usr/bin/env perl

# Read the file
my $file = 'public/index.php';
my $data;
{
	open my $fh, '<', $file or die "Failed to open file $file: $!";
	local $/ = undef;
	$data = <$fh>;
	close $fh or die "Unable to close $file: $!";
}

# Edit it
my $anchor = '<!-- End of head -->';

my $php = <<'END';
<?php $tracking_html = @file_get_contents(dirname(__FILE__, 2) . '/src/tracking.html'); if ($tracking_html) echo $tracking_html; ?>
END
$data =~ s/$anchor/$php/;

# Write it back
{
	open my $fh, '>', $file or die "Failed to open file $file: $!";
	print { $fh } $data;
	close $fh or die "Unable to close $file: $!";
}