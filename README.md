# Circuit Science website

## Server requirements

- PHP 8.1 or newer
- Composer
- PHP extensions: OpenSSL, fileinfo and session
- Web document root set to `public/`

## Installation

1. Copy `.env.example` to `.env` in the project root.
2. Replace every SMTP placeholder in `.env` with the WHC mail-server settings.
3. Run `composer install --no-dev --optimize-autoloader` in the project root.
4. Configure the domain document root as the project's `public/` directory.
5. Submit a test request and confirm that it arrives at the configured recipient.

Never commit `.env`; it contains the SMTP password.
