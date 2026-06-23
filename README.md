# iWorks GT Service Portal

A production-oriented service operations platform built with PHP and MySQL. It brings client ordering, payments, project delivery, support, agent collaboration, and administrative workflows into one responsive portal.

[View the live product](https://portal.iworksgt.com/)

## Portfolio case study

**Role:** Full-stack developer

**Focus:** Product engineering, backend workflows, payments, authentication, responsive UI, deployment, and operations

I designed and built this platform to replace fragmented service-management processes with one system for customers, agents, finance staff, and administrators. The project demonstrates my ability to take a substantial web product from database design and business rules through interface development, third-party integrations, security controls, testing, and production deployment.

### Engineering scope

- Modelled multi-role workflows for ordering, payment approval, job assignment, delivery, commissions, and withdrawals
- Integrated automatic and manual payment flows across local and international currencies
- Implemented signed SSO, email OTP, trusted-device sessions, OAuth, and password-recovery controls
- Built configurable CMS, service-catalog, blog, media-library, and intake-form tools for non-technical administrators
- Added operational exports, notification flows, runtime schema upgrades, smoke tests, and continuous integration

## Highlights

- Multi-service checkout with NGN and USD support
- Paystack, Flutterwave, Stripe, bank transfer, and crypto payment flows
- Client, agent, finance, and administrator role-based dashboards
- Job assignment, delivery uploads, comments, and commission tracking
- Support tickets, direct messaging, community posts, and notifications
- Email OTP, trusted devices, password recovery, and signed SSO handoff
- Configurable public website, service catalog, blog, media library, and page builder
- Custom intake forms with optional AI-assisted schema generation
- CSV reporting for users, jobs, invoices, payments, payouts, and withdrawals

## Technology

| Layer | Technology |
| --- | --- |
| Backend | PHP 8+, PDO |
| Database | MySQL 5.7+ / MariaDB 10.4+ |
| Frontend | Server-rendered PHP, HTML, CSS, vanilla JavaScript |
| Web server | Apache with `mod_rewrite` |
| Integrations | Stripe, Paystack, Flutterwave, Google OAuth, OpenAI |

## Project structure

```text
.
├── api/                 # JSON and AI-assisted form endpoints
├── app/                 # Authentication, database, domain, and helper logic
├── assets/              # Styles and public upload placeholders
├── storage/             # Protected runtime uploads
├── tests/               # Lightweight PHP smoke tests
├── views/               # Public and authenticated UI templates
├── database.sql         # Initial database schema
├── index.php            # Main application router
└── setup.php            # Web-based installation and schema setup
```

## Local setup

### Requirements

- PHP 8.0 or newer with PDO MySQL
- MySQL 5.7+ or MariaDB 10.4+

### Installation

1. Clone the repository.
2. Create a database and import `database.sql`.
3. Copy the example configuration:

   ```bash
   cp app/config.example.php app/config.php
   ```

4. Update the database credentials and SSO secret in `app/config.php`, or provide the matching environment variables.
5. Start a local PHP server:

   ```bash
   php -S localhost:8000
   ```

6. Open `http://localhost:8000/setup.php` and create the first administrator.

Never commit `app/config.php`; it is ignored because it contains environment-specific secrets.

## Configuration

The example configuration supports these environment variables:

```text
APP_NAME
APP_BRAND_NAME
APP_BASE_URL
APP_TIMEZONE
DB_HOST
DB_PORT
DB_NAME
DB_USER
DB_PASS
SSO_SHARED_SECRET
```

Payment gateway, Google OAuth, email, and AI provider credentials are configured by an administrator after installation.

## Tests

Run the smoke tests:

```bash
php tests/crypto_conversion_test.php
php tests/public_cms_layout_test.php
```

Lint all PHP files:

```bash
find . -type f -name '*.php' -print0 | xargs -0 -n1 php -l
```

GitHub Actions runs the same checks on every push and pull request.

## Deployment

The application is designed for standard Apache/cPanel hosting:

1. Point the domain or subdomain document root at the repository directory.
2. Create a MySQL database and user with the required privileges.
3. Copy and configure `app/config.php`.
4. Ensure `storage/uploads` and `assets/uploads` are writable by PHP.
5. Visit `setup.php` once to initialize or upgrade the schema.
6. Keep the generated `storage/setup.lock` in place after setup.
7. Enforce HTTPS and configure gateway webhooks for the production domain.

## Security notes

- CSRF validation and role-based authorization are built into protected workflows.
- Uploads use type and size restrictions and are stored behind server rules.
- Sensitive configuration, runtime logs, setup locks, and user uploads are excluded from Git.
- Production credentials should be stored only in `app/config.php`, environment variables, or a secret manager.

Please report security issues privately as described in [SECURITY.md](SECURITY.md).

## Status

This repository is an actively developed portfolio and production case study. It demonstrates end-to-end product engineering across authentication, payments, workflow automation, content management, and operational reporting.
