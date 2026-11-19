# Letter verification — cPanel deployment handoff

The verify feature is committed in this repository but the live site
(www.bareeda-injera.com) runs an older copy. Upload these files with cPanel
File Manager or FTP (paths relative to the site root, usually `public_html/`):

## Files to upload

| Local file | Destination |
|---|---|
| `classes/VerifyStore.php` | `classes/` |
| `classes/ErpClient.php` (updated) | `classes/` |
| `controllers/VerifyController.php` (updated) | `controllers/` |
| `views/verify/show.php` (updated) | `views/verify/` |
| `assets/css/verify.css` (updated) | `assets/css/` |
| `public/index.php` (updated — adds the ingest route) | `public/` |
| `config/config.php` (updated — adds ERP_INGEST_SECRET) | `config/` |
| `.htaccess` — verify routes already present; no change needed | root |

## One-time database step

In cPanel → phpMyAdmin → select the site database (see `DB_NAME` in
`config/config.php`), open the SQL tab and run the contents of:

```
database/verify-table.sql
```

## One-time configuration

In `config/config.php` (or the hosting environment variables), set:

```php
define('ERP_INGEST_SECRET', '<the VERIFY_PUBLISH_SECRET value from the ERP .env.local>');
```

The matching value lives in the factory ERP's `.env.local` as
`VERIFY_PUBLISH_SECRET` — both sides must be identical. Keep it private.

## Then test end-to-end

1. Issue (or re-issue) a letter in the ERP — it pushes its snapshot to the site.
2. Check phpMyAdmin: `letter_verifications` should hold the row.
3. Scan the letter's QR — the page must show Verified with the check code
   and security seal **without contacting the factory**.
4. Revoke a test letter in the ERP → the public page flips to "Cancelled".
