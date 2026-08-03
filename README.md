# Doctomatique

A PHP/MySQL web application inspired by Doctolib, built as a school project. It lets you search for practitioners, check their availability, and manage appointments from the patient, practitioner, secretary, and administrator sides.

This is a learning project: it has no real patients, no real practitioners, and no real medical or personal data. Any data used is fictional or generated for demonstration purposes.

## Overview

- `index.php` — home page
- `pages/` — the application's PHP pages
- `api/` — AJAX endpoints for search, booking, and cancelling appointments
- `assets/css/` — stylesheets
- `assets/js/` — front-end scripts
- `assets/images/` — images and icons

## Main features

- Search for practitioners by specialty and city
- View a practitioner's profile and available slots
- Book and cancel appointments
- Patient area
- Practitioner area
- Secretary area
- Administrator area

## Prerequisites

- PHP 8.x with the `mysqli` and `pdo_mysql` extensions
- MySQL or MariaDB
- A local web server, or PHP's built-in server

## Local installation

1. Clone or copy the project to your machine.
2. Copy `config.local.example.php` to `config.local.php`.
3. Fill in your database credentials in `config.local.php`.
4. Create a MySQL/MariaDB database and its tables to match the schema
   expected by the code in `pages/` and `api/` (this repository does not
   include a schema dump).
5. Run the project with your usual setup.

Example using PHP's built-in server:

```bash
php -S localhost:8000
```

Then open `http://localhost:8000`.

## Configuration

The project first loads the default values defined in `pages/config.php`, then applies, if present:

- the environment variables `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_CHARSET`, `APP_TIMEZONE`
- the untracked local file `config.local.php`

This keeps secrets out of the public repository.

## Notes

- This repository is set up to be public without exposing any database credentials.
- The code has intentionally been kept close to its original structure — this is a small pedagogical project, not a production system.

## License

Distributed under the MIT License. See [LICENSE](LICENSE) for details.
