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
- Open `status.php` in your browser and check that everything is in order.

## License

This project is a rewrite or clone of nokonoko/Uguu, so it is also licensed under the [MIT License](LICENSE).

---

## Developer notes

### Design goals and caveats

Uweh strives to be simple, not complex. This means no database, no libraries, a small number of files, downloads being directly handled by the webserver and decent documentation.

This leads to some drawbacks: there is no upload progress bar, and the filesize is limited by the webserver.

### Execution overview

`index.php` mostly handles the HTML, and some argument preprocessing. It hands off the processing to `Uweh\save_file(…)` which is the main function. That returns the filepath, which is passed to `Uweh\get_download_url` to turn it into a url.

The about page is handled in `index.php` when requesting `?about`.
The api page `api.php` calls the same functions as `index.php` and formats it nicely.

The file expiration is handled by the cleanup script `./src/clean_files.sh` which reads the configuration file `config.php`.

To store the files, Uweh creates a lot of 2-letter subfolders in the files directory: this is to prevent filename collision. The uploaded files are then stored in one of those folders. (You could customize the subfolder length by editing `Uweh.php`).

### TODO

- Maybe move the javascript to another file ?
- Document `main.css`, `Uweh.php` and `index.php`
- Display a warning message if the user selects a file that will be rejected (in addition to the red outline around the input field).

### Documentation status

```text
--    src/                   Source files
        Uweh.php               Main library file
        UwehTpl.php            Page fragments to make index.php more straight forward
OK      config.template.php    Configuration file template
OK      clean_files.sh         File cleanup script run by cron
--    bin/                   Installation helper scripts
OK      protect_status.pl      Set status.php's password
OK      set_permissions.sh     Set file permissions
--    public/               Webserver root
        index.php             Main page
        about.php             About page
OK      api.php               Api page
OK      status.php            Status page
        main.css              Main stylesheet
```

## Docs

What do all these files do ?

TODO

We inline CSS for the main page, but use the external file for all others to save that extra blocking request