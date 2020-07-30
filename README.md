# Uweh

Ephemeral file hosting. An [Uguu](https://github.com/nokonoko/Uguu) clone.

Users upload files which are hosted for a set period of time until they get deleted.

## Installation

Requirements:
* PHP version >= 7.0 (because of `??`, `define(…, array(…))`)
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
- Optional: Create a virtual filesystem and mount it at `/public/files` to limit disk usage (cf. [AskUbuntu question](https://askubuntu.com/questions/841282/how-to-set-a-file-size-limit-for-a-directory))
- Customization
  - Customize the About page, especially the contact email.
  - Customize the html title in both `index.php` and `about.php`.
  - Upload a background image at `/img/riamu.png` and a favicon at `/favicon.png`

## License

This project is a rewrite or clone of nokonoko/Uguu, so it is also licensed under the [MIT License](LICENSE).

## Developer notes

### Overview

`index.php` mostly handles the HTML, and some argument preprocessing. It hands off
the processing to `Uweh\process(…)`.

### Caveats

- It would be nice to strip off the random prefix when serving the file by setting the `Content-Disposition` header, but I couldn't find a way to do that nicely in the webserver.

### Notes

* We check the filename as it is saved on the server.
* Empty files are allowed so one could make us run out of inodes by uploading very small files.
* With a 12 character prefix, you need at least 1.9e12 uploaded files to get a 1% probability of at least generating a name thrice.
* If a filename has an underscore, it has a user-submitted name.
* Generated filename grammar:
  ```raku
  regex rand-char { <{"abdefghjknopqstuvxyzABCDEFHJKLMNPQRSTUVWXYZ345679".comb}> }
  regex file-char { <-[ \0 \/ \\ ]> }
  regex filename {
      | <rand-char> ** 12 [ "." <file-char>* ]?
      | <chars> ** 12 "_" <file-char>+
  }
  ```