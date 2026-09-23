# Circuit Science website

## Server requirements

- PHP 8.0 or newer
- PHP extensions: PDO MySQL, fileinfo and session
- A hosting account with PHP `mail()` configured
- Web document root set to `public/`

## Installation

1. Copy `.env.example` to `.env` in the project root.
2. Replace every password or secret placeholder with a unique value.
3. Set the recipient and sender email addresses for the hosting account.
4. Configure the domain document root as the project's `public/` directory.
5. Ensure the project-level `storage/` directory is writable by PHP.
6. Submit a test request and confirm that it is saved and the email arrives.

No Composer packages are required at runtime. Never commit `.env`; it contains database and admin credentials.

## Existing database upgrades

After deploying the job-material catalog, import `database/add_job_materials.sql` once through phpMyAdmin. The script is idempotent and may be run again safely; it creates the lookup tables and empty material catalog without adding rarely used materials.

## Product photos

Upload GIF, JPG, PNG or WebP product images to `public/admin/assets/product_pics/`. The job-material editor discovers files in that directory automatically and stores their web paths; no image-upload PHP extension or Composer package is required.
