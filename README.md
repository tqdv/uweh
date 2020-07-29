# Uweh

Ephemeral file hosting. An [Uguu](https://github.com/nokonoko/Uguu) clone.

## Requirements

* PHP version >= 7.0 (because of `??`, `define(…, array(…))`)
* Perl, a shell and the find command (for the cleanup script)

Recommended:
- php-mbstring: if you want to truncate a filename correctly

## Installation

- Get the files on your server (to build a tarball, use `tar cf uweh.tar.gz public/ src/ README.md`)
- Copy `src/config.template.php` to `src/config.php` and customize it to your liking. 
- Configure the web server to serve php files (`/public/`) and the uploaded files (`/public/files/` or your custom directory).
- Add the file cleaning job to your crontab:
  ```cron
  0,15,30,45 * * * * sh /path/to/uweh/src/clean_files.sh
  ```
- Make sure that filesize limits in php.ini (`upload_max_filesize`), and your webserver (`client_max_body_size` for nginx, `LimitRequestBody` for Apache) are larger than `UWEH_MAX_FILESIZE`.
- Optional: Create a virtual filesystem and mount it at `/public/files` to limit disk usage
- Open `status.php` in your browser and check that everything is in order
- Move `status.php` to `status.txt` to protect sensitive information
- Customization
  - Change the contact email in `about.php` as well as the link target.
  - Customize the html title in both `index.php` and `about.php`.
  - Customize the About page
  - Upload the background image at `/img/riamu.png`
  - Upload a favicon at `/favicon.png`

## License

MIT

## Caveats

- It would be nice to strip off the random prefix when serving the file by setting the `Content-Disposition` header, but I couldn't find a way to do that nicely in the webserver.

## Notes

* We check the filename as it is saved on the server.
* Empty files are allowed so one could make us run out of inodes by uploading very small files.
* With a 12 character prefix, you need at least 1.9e12 uploaded files to get a 1% probability of at least generating a name thrice.
* If a filename has an underscore, it has a user-submitted name.
* ```raku
  regex rand-char { <{"abdefghjknopqstuvxyzABCDEFHJKLMNPQRSTUVWXYZ345679".comb}> }
  regex file-char { <-[ \0 \/ \\ ]> }
  regex filename {
      | <rand-char> ** 12 [ "." <file-char>* ]?
      | <chars> ** 12 "_" <file-char>+
  }
  ```