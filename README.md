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

## Async Notifications Worker

Email notifications for property moderation are processed asynchronously via Symfony Messenger (`async_notifications` queue).

### Development

Run all services (including worker):
```bash
docker compose up -d
```

Run only worker:
```bash
docker compose up -d worker
docker compose logs -f worker
```

### Production

Install PHP dependencies inside the `php` container (use `--no-dev` on servers; omit it only when you need dev tools). After changing `docker/php/Dockerfile`, rebuild: `docker compose -f docker-compose.prod.yml build php worker`.

```bash
docker compose -f docker-compose.prod.yml run --rm --no-deps php sh -lc \
  "cd /var/www/backend && composer install --no-dev --no-interaction --optimize-autoloader"
```

If you still hit permission errors on `vendor/`, fix ownership on the host (example: `sudo chown -R \"$USER\" backend/vendor` or remove `vendor` and re-run the command above as the same user that owns `./backend`).

Start worker with production compose:
```bash
docker compose -f docker-compose.prod.yml up -d worker
docker compose -f docker-compose.prod.yml logs -f worker
```

Required env vars for email notifications:
- `MAILER_DSN`
- `MAILER_FROM`
- `ADMIN_EMAILS` (JSON array, e.g. `["admin@rnb.by"]`)
- `FRONTEND_URL`
- `MESSENGER_TRANSPORT_DSN` (defaults in `docker-compose.prod.yml` to `doctrine://default?auto_setup=0`; must match a running `worker` and the `messenger_messages` table — run `php bin/console messenger:setup-transports` once if needed)

### Smoke Check (Dev, Mailpit)

1. Start services and worker:
   ```bash
   docker compose up -d
   docker compose up -d worker
   ```
2. Make sure worker is consuming queue:
   ```bash
   docker compose logs -f worker
   ```
   You should see `messenger:consume async_notifications` in logs.
3. Create or publish a property (status goes to moderation), or reject/approve a revision.
4. Open Mailpit UI: [http://localhost:8025](http://localhost:8025)
5. Verify email delivery:
   - owner gets `submitted/approved/rejected` email depending on action
   - admins from `ADMIN_EMAILS` get moderation email on submit
6. If email is not sent, check:
   ```bash
   docker compose logs -f worker
   docker compose exec php php bin/console messenger:stats
   ```
   If messages are pending, ensure worker is running and `MESSENGER_TRANSPORT_DSN` is configured.

## API Documentation
See [Backend API Summary](backend_api_summary.md) for detailed endpoint documentation.

## Project Structure
- `src/Domain`: Entities, Value Objects, Repository Interfaces (Core Business Logic).
- `src/Application`: Commands, Queries, Handlers (Use Cases).
- `src/Infrastructure`: Implementations (Doctrine, Services, etc.).
- `src/Presentation`: Controllers, Requests, Responses (API).

## Contributing
Please follow the defined coding standards and architecture principles (DDD/CQRS).
