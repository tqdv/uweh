# Uweh

Ephemeral file hosting. An [Uguu](https://github.com/nokonoko/Uguu) clone before it switched to Pomf.

Users can upload files which are hosted for a set period of time before they are removed.

Changelog: See [CHANGELOG.md](CHANGELOG.md).

## Installation

Requirements:
* PHP version >= 7.1 (because of `??`, `define(…, array(…))`, `function (…) : ?type` )
* Perl, a shell and the find command (for the cleanup script)
* Optional: php-mbstring if you want to truncate a filename correctly

Deployment steps:
- Copy `src/config.template.php` to `src/config.php` and customize it to your liking. 
- Configure the web server to serve php files (`/public/`) and the uploaded files (eg. `/public/files/`). Make sure to disable php execution in the files directory or use a subdomain to be certain.
- Add the file cleaning job to your crontab:
  ```cron
  0,15,30,45 * * * * sh /path/to/uweh/src/clean_files.sh
  ```
- Make sure that filesize limits in php.ini (`upload_max_filesize`), and your webserver (`client_max_body_size` for nginx, `LimitRequestBody` for Apache) are larger than `UWEH_MAX_FILESIZE`.
- Open `status.php` in your browser and check that everything is in order. Then delete or rename it for security.
- Customization
  - Customize the About page, especially the contact email.
  - Customize the html title in both `index.php` and `about.php`.
  - Upload a background image at `/img/riamu.png` and a favicon at `/favicon.png`

## License

This project is a rewrite or clone of nokonoko/Uguu, so it is also licensed under the [MIT License](LICENSE).

## Developer notes

### Context

List of rules I imposed on myself:
- No database
- No external php libraries
- Single library file
- Most things should be documented
- Downloading is only handled by the webserver
- User downloads the file with the right filename
- User should not be able to guess a file URL

### Overview

`index.php` mostly handles the HTML, and some argument preprocessing. It hands off
the processing to `Uweh\save_file(…)` which is the main function.

The file expiration is handled by the cleanup script `./src/clean_files.sh` which reads the configuration file. 

Uweh creates a lot of 2-letter subfolders in the files directory: this is to prevent filename collision.
The uploaded files are then stored in one of those folders.

### TODO

- Display a warning message if the user selects a file that will be rejected (in addition to the red outline around the input field).
- Rewrite config template to have better documentation

### Caveats

- There is no upload progress bar
- File size is limited by webserver limits.

### Notes and ideas

* Double quotes, backslashes and newlines are not allowed in the file folder pathname (cf. `clean_files.sh` FILE_ROOT regex).
* You need a case-sensitive filesystem
* There may be a race condition where two users upload the same filename (uncommon) at the same time (unlikely) and generate the same filepath (rare) which overwrites the other one. This is not handled.
* You could create a virtual filesystem and mount it at `/public/files` to limit disk usage (cf. [AskUbuntu question](https://askubuntu.com/questions/841282/how-to-set-a-file-size-limit-for-a-directory))