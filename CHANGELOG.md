# Changelog

## [v2.4] – 2021-03-07

- Add php.ini `post_max_size` check in `status.php`.

## [v2.3] – 2020-12-21

- Implement an MVC (?) pattern by creating UwehTpl
- Implement Post-Redirect-Get pattern to prevent form resubmission
- Use `riamu.webp` to save bandwidth

## [v2.2] – 2020-12-05

- Add drap and drop support
- Add option to include tracking html in head

## [v2.1] – 2020-11-29

- Add copy link button
- Rework executation overview

## [v2.0] – 2020-10-26

Rethink storage so that the user downloads the right filename (without the random prefix).
Remove `about.php` by merging it into `index.php`.

Add deployment scripts and password protection for `status.php`.
