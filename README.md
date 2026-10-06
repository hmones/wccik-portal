# WCCIK Membership Portal

An online membership portal for the **Women Chamber of Commerce & Industry, Karachi (WCCIK)**. It allows applicants to submit new membership applications or renewals, track their application status, and gives the WCCIK secretariat an admin panel to review, verify, and approve submissions.

The portal is fully bilingual — English and Urdu — with RTL support for Urdu.

## Built With

- **Laravel 13** + **PHP 8.3** — backend
- **Inertia.js v3** + **Vue 3** — frontend (SPA without the complexity)
- **Tailwind CSS 4** — styling
- **MySQL 8** — database (Docker)
- **Laravel Nova 5** — admin panel
- **spatie/laravel-translatable** — translatable model fields
- **Laravel Wayfinder** — type-safe route bindings for the frontend

## Getting Started

### Prerequisites

- PHP 8.3+
- Composer
- Node.js + npm
- Docker Desktop

### 1. Install dependencies

```bash
composer install
npm install
```

### 2. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set your `NOVA_LICENSE_KEY`. The database is pre-configured for the Docker setup below.

### 3. Start MySQL

```bash
docker compose up -d
```

MySQL runs on host port **3310** to avoid conflicts. Credentials: database `pakistan_database`, user `pakistan_db`, password `secret`.

### 4. Run migrations

```bash
php artisan migrate
```

### 5. Start the development environment

```bash
php artisan dev
```

This starts the Laravel server, Vite, queue worker, and log tail together. The app will be available at **http://localhost:8000**.

### 6. Local email (Mailpit)

Outgoing emails (OTP codes for renewal, future status notifications) are sent over SMTP to **Mailpit** — a local inbox that renders mail exactly like a real client, without delivering it externally.

`.env.example` is pre-configured to send to `127.0.0.1:1025`, and `docker-compose.yml` includes a Mailpit service under the `mail` profile so it does not start by default (prevents port clashes if you already run Mailpit for another project).

Start it when you need it:

```bash
docker compose --profile mail up -d mailpit
```

Then open the inbox at **http://localhost:8025**.

Any `php artisan tinker` call or form that triggers a mail (e.g. requesting a renewal OTP at `/renew`) will land there. If you already have a Mailpit instance running on ports `1025`/`8025` from another project, you can skip the command above — Laravel will use that one automatically.

To silence local mail entirely, set `MAIL_MAILER=log` in your `.env` — emails will append to `storage/logs/laravel.log` instead.

## Admin Panel

The Nova admin panel is at **http://localhost:8000/nova**. Access is restricted to authorised users configured in `app/Providers/NovaServiceProvider.php`.

## Public Routes

| Route | Description |
|---|---|
| `/` | Landing page |
| `/apply` | New membership application form |
| `/apply/confirmation/{token}` | Submission confirmation with status link |
| `/renew` | Renewal — membership number entry (sends OTP to registered email) |
| `/renew/verify` | OTP verification |
| `/renew/form` | Renewal form (pre-filled from member record) |
| `/renew/confirmation/{token}` | Renewal submission confirmation |
| `/status/{token}` | Application status check *(coming soon)* |

## Code Style

```bash
# PHP
./vendor/bin/pint

# JS / Vue
npm run lint

# Tests
php artisan test
```
