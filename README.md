# Uweh

Ephemeral file hosting. An [Uguu](https://github.com/nokonoko/Uguu) clone before it switched to Pomf.

Users can upload files which are hosted for a set period of time before they are removed.

Changelog: See [CHANGELOG.md](CHANGELOG.md).

## Installation

_NB I have only tested this on PHP 7.2._

Requirements:
* PHP version >= 7.1 (because of `??`, `define(…, array(…))`, `function (…) : ?type` )
* Perl, a shell and the find command (for the cleanup script)
* Optional: php-mbstring if you want to truncate a filename correctly

Deployment steps:
- Copy `src/config.template.php` to `src/config.php` and customize it to your liking. 
- Configure the web server to serve php files and the uploaded files. Make sure that it doesn't execute user-uploaded php files, and that the file size limits in php.ini (`upload_max_filesize`), and your webserver (`client_max_body_size` for nginx, `LimitRequestBody` for Apache) are larger than `UWEH_MAX_FILESIZE`.
- Add the file cleaning job to your crontab:
  ```cron
  0,15,30,45 * * * * sh /path/to/uweh/src/clean_files.sh >/dev/null 2>&1 
  ```
- Open `status.php` in your browser and check that everything is in order. If so, delete it for security.

## License

This project is a rewrite or clone of nokonoko/Uguu, so it is also licensed under the [MIT License](LICENSE).

---

## Developer notes

### Design goals and caveats

Uweh strives to be simple, not complex. This means no database, no libraries, a small number of files, downloads being directly handled by the webserver and decent documentation.

This leads to some drawbacks: there is no upload progress bar, and the filesize is limited by the webserver.

### Execution overview

`index.php` is the main entry point. It displays the HTML form using `UwehTpl`.

The user can upload a file by filling out the form, which will send an HTTP POST to `index.php`.
The file will be saved using `Uweh\save_file` and the user will be redirected to `upload.php`,
passing the saved filepath or the error code through the GET arguments. The redirection prevents form resubmission ie. double uploads.

`upload.php` displays the file download link with `Uweh\get_download_url`, or the error message based on GET arguments.
If no arguments are present, it just redirects to `index.php`.

The about page is… `about.php`.

The api page `api.php` calls the same functions as `index.php` (`Uweh\save_file`, etc…)and formats it nicely.

File expiration is handled by the cleanup script `./src/clean_files.sh` which reads the configuration file `config.php`.

To store the files, Uweh creates a lot of 2-letter subfolders in the files directory: this is to prevent filename collision. The uploaded files are then stored in one of those folders. (You _could_ customize the subfolder length by editing `Uweh.php`).

### TODO

- Document `main.css`
- Display a warning message if the user selects a file that will be rejected (in addition to the red outline around the input field).

### Documentation status

```text
--    src/                      Source files
OK      Uweh.php                  Main library file
OK      UwehTpl.php               Page fragments to make index.php more straight forward
OK      config.template.php       Configuration file template
OK      clean_files.sh            File cleanup script run by cron
--    bin/                      Installation helper scripts
OK      protect_status.pl         Set status.php's password
OK      set_permissions.sh        Set file permissions
OK      add_tracking_include.pl   Make Uweh include tracking.html
--    public/                   Webserver root
OK      index.php                 Main page
OK      upload.php                Upload success page
OK      about.php                 About page
OK      api.php                   Api page
OK      status.php                Status page
        main.css                  Main stylesheet
```
