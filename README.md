# Real Estate Portal (Belarus) - Backend

## Overview
A modern real estate portal backend built with Symfony 7, adopting Domain-Driven Design (DDD) and CQRS principles. The application provides a robust API for property listings, user management, and content (articles).

## Technology Stack
- **Framework**: Symfony 7.2
- **Language**: PHP 8.4
- **Database**: MySQL 8.0
- **ORM**: Doctrine
- **Architecture**: DDD, CQRS (Symfony Messenger)
- **Authentication**: JWT (LexikJWTAuthenticationBundle)
- **Containerization**: Docker & Docker Compose

## Key Features
- **Properties**: CRUD operations, Advanced Search (price, area, location), Image support.
- **Users**: Registration, Authentication, Profile Management.
- **Articles**: Blog/News functionality with publishing workflow.
- **Reference Data**: Managing Cities and Locations.
- **File Uploads**: Secure image upload service.

## Getting Started

### Prerequisites
- Docker & Docker Compose
- Make (optional, but recommended)

### Installation
1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd rnb-by
   ```

2. **Start Docker Containers**
   ```bash
   make up
   # OR
   docker-compose up -d
   ```

3. **Install Dependencies**
   ```bash
   make composer-install
   ```

4. **Setup Database**
   ```bash
   make migration-migrate
   # Run seed data (optional)
   # php bin/console app:seed-cities
   ```

5. **Generate JWT Keys**
   ```bash
   make jwt-generate-keys
   ```
   Dev keys may live in `backend/config/jwt/` (see `backend/.env.example`). Do not use committed keys in production — generate new PEM files and set `JWT_PASSPHRASE`.

### Environment: SMS rate limits and reCAPTCHA v2

- **`SMS_*`** — throttle SMS sends per phone, per IP, and globally (`DoctrineSmsRateLimiter`). Defaults are suitable for dev; tune for production.
- **`RECAPTCHA_ENABLED`** / **`RECAPTCHA_SECRET`** — when `RECAPTCHA_ENABLED=1`, the API requires a valid `recaptchaToken` on `POST /api/auth/sms/request` and `POST /api/contact`. The Next.js app uses `NEXT_PUBLIC_RECAPTCHA_SITE_KEY` (see `docker-compose.yml`).

Copy variables from [`backend/.env.example`](backend/.env.example) into `backend/.env`.

### Migrations vs existing database

If the database already has tables but `doctrine_migration_versions` is empty, a blind `migrate` will fail. Mark already-applied versions with `php bin/console doctrine:migrations:version DoctrineMigrations\\Version... --add` (for each version up to your current schema), then run `doctrine:migrations:migrate` for the remaining ones.

## Email notifications

Property moderation, contact feedback, and related emails are sent **synchronously** in the same HTTP request (Symfony Messenger `notification.bus`, no queue worker).

Required env vars:
- `MAILER_DSN`
- `MAILER_FROM`
- `ADMIN_EMAILS` (JSON array, e.g. `["admin@posutki.by"]`)
- `FRONTEND_URL`

### Smoke check (dev, Mailpit)

1. `docker compose up -d`
2. Create or publish a property (moderation), or submit the contact form.
3. Open Mailpit: [http://localhost:8025](http://localhost:8025)
4. Verify: owner gets submit/approve/reject mail when applicable; admins from `ADMIN_EMAILS` get moderation mail on submit.
5. If mail is missing, check `backend/var/log/dev.log` and `MAILER_DSN` / `ADMIN_EMAILS`.

## API Documentation
See [Backend API Summary](backend_api_summary.md) for detailed endpoint documentation.

## Project Structure
- `src/Domain`: Entities, Value Objects, Repository Interfaces (Core Business Logic).
- `src/Application`: Commands, Queries, Handlers (Use Cases).
- `src/Infrastructure`: Implementations (Doctrine, Services, etc.).
- `src/Presentation`: Controllers, Requests, Responses (API).

## Contributing
Please follow the defined coding standards and architecture principles (DDD/CQRS).
