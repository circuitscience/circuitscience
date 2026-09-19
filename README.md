# Circuit Science website

## Server requirements

- PHP 8.1 or newer
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
