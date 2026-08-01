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

## Admin Panel

The Nova admin panel is at **http://localhost:8000/nova**. Access is restricted to authorised users configured in `app/Providers/NovaServiceProvider.php`.

## Public Routes

| Route | Description |
|---|---|
| `/` | Landing page |
| `/apply` | New membership application form |
| `/apply/confirmation/{token}` | Submission confirmation with status link |
| `/renew` | Renewal form *(coming soon)* |
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
