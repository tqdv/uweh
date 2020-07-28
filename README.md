# Uweh

Ephemeral file hosting

Minimal PHP version: 7.0 (because of `??`, `define(…, array(…))`)

Recommended:
- mbstring: if you need to truncate a filename correctly

## Installation

- Copy `src/config.template.php` to `src/config.php` and customize it to your liking. 
- Configure the web server for `index.php`, `prereq.php` (and `api.php` if needed)
- Add the file cleaning job to your crontab:
  ```cron
  0,15,30,45 * * * * sh /path/to/uguu/src/clean_files.sh
  ```
- Optionally: configure your web server to set the `Content-Disposition` header appropriately for the files
- Open `status.php` in your browser and check that everything is in order
- Move `status.php` to `status.txt` to protect sensitive information


## Todo

- Content-Disposition in nginx config

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