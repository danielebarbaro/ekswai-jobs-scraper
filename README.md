# EksWai Position Scraper

> Laravel 12 application with DDD & Hexagonal Architecture for monitoring Workable job positions.

[![Tests](https://github.com/danielebarbaro/ekswai-jobs-scraper/actions/workflows/tests.yml/badge.svg)](https://github.com/danielebarbaro/ekswai-jobs-scraper/actions/workflows/tests.yml)
[![Static Analysis](https://github.com/danielebarbaro/ekswai-jobs-scraper/actions/workflows/analyse.yml/badge.svg)](https://github.com/danielebarbaro/ekswai-jobs-scraper/actions/workflows/analyse.yml)
[![Linter](https://github.com/danielebarbaro/ekswai-jobs-scraper/actions/workflows/lint.yml/badge.svg)](https://github.com/danielebarbaro/ekswai-jobs-scraper/actions/workflows/lint.yml)

## Overview

**EksWai Position Scraper** is a Laravel 12 application built with Domain-Driven Design (DDD) and Hexagonal Architecture. It allows users to:

- Register and manage a list of companies (Workable accounts) to track
- Automatically fetch job postings from Workable's public API
- Detect and store new job positions
- Receive email notifications when new jobs are found
- Manage everything through a Filament 4 admin panel

## Features

- ✅ **DDD & Hexagonal Architecture** - Clean separation of concerns
- ✅ **Laravel 12** with PHP 8.4
- ✅ **PostgreSQL** - Production database with UUIDs
- ✅ **Filament 4** - Modern admin panel
- ✅ **Workable Integration** - Automatic job scraping
- ✅ **Email Notifications** - HTML emails for new positions
- ✅ **Scheduled Tasks** - Daily automatic sync
- ✅ **Code Quality** - PHPStan, Pint, Rector
- ✅ **Testing** - Pest for unit and feature tests
- ✅ **CI/CD** - GitHub Actions workflows

## Technical Stack

| Component | Technology |
|-----------|------------|
| Framework | Laravel 12 |
| PHP Version | 8.4+ |
| Local Environment | Laravel Sail (Docker) |
| Database | PostgreSQL (SQLite for tests) |
| Admin Panel | Filament 4 |
| Testing | Pest |
| Static Analysis | PHPStan (Larastan) |
| Code Style | Laravel Pint (PSR-12) |
| Refactoring | Rector |

## Architecture

```
app/
├── Domain/              # Business logic layer
│   ├── Company/
│   ├── JobPosting/
│   ├── Shared/
│   └── User/
├── Application/         # Use case orchestration
│   ├── Actions/
│   └── DTOs/
└── Infrastructure/      # External adapters
    ├── Admin/
    │   └── Filament/
    ├── Console/
    ├── Mail/
    └── Services/
        └── Workable/
```

## Installation

### Prerequisites

- PHP 8.4+
- Composer
- Docker & Docker Compose (for Sail)
- Node.js & NPM (optional, for frontend assets)

### Quick Start

1. **Clone the repository**

```bash
git clone https://github.com/danielebarbaro/ekswai-jobs-scraper.git
cd ekswai-jobs-scraper
```

2. **Install dependencies**

```bash
composer install
```

3. **Set up environment**

```bash
cp .env.example .env
php artisan key:generate
```

4. **Start Laravel Sail (Docker)**

```bash
./vendor/bin/sail up -d
```

5. **Run migrations**

```bash
./vendor/bin/sail artisan migrate
```

6. **Create a Filament admin user**

```bash
./vendor/bin/sail artisan make:filament-user
```

7. **Access the application**

- Admin Panel: http://localhost/admin
- Login with the credentials you just created

## Usage

### Managing Companies

1. Log in to the Filament admin panel at `/admin`
2. Navigate to **Companies**
3. Click **New Company** and enter:
   - **Name**: Display name for the company
   - **Workable Account Slug**: The identifier from the Workable URL
     (e.g., `company-name` from `https://apply.workable.com/company-name`)
   - **User**: Select which user owns this company
   - **Active**: Toggle to enable/disable tracking

### Running the Sync

**Manual sync:**

```bash
./vendor/bin/sail artisan jobs:sync-daily
```

**Automatic sync:**

The sync runs automatically every day at 9:00 AM UTC via Laravel's scheduler.

In production, add this to your crontab:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Email Notifications

When new job positions are found, users receive an HTML email with:
- List of new jobs grouped by company
- Job title, location, and department
- Direct links to apply

Configure your mail settings in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS="noreply@ekswai.com"
MAIL_FROM_NAME="EksWai Position Scraper"
```

## Development

### Code Quality

**Run code linter:**

```bash
composer lint
```

**Static analysis:**

```bash
composer analyse
```

**Refactoring suggestions:**

```bash
composer rector-dry
```

**Run all checks:**

```bash
composer check
```

### Testing

**Run tests:**

```bash
composer test
```

**With coverage:**

```bash
./vendor/bin/sail artisan test --coverage
```

### Database

**Fresh migration:**

```bash
./vendor/bin/sail artisan migrate:fresh
```

**Seed data (if seeders exist):**

```bash
./vendor/bin/sail artisan db:seed
```

## Deployment

The application is production-ready and can be deployed to any Laravel-compatible hosting:

- **Forge** - Laravel Forge (recommended)
- **Vapor** - AWS Lambda
- **Docker** - Using the Sail configuration
- **Traditional** - Apache/Nginx + PHP-FPM

Ensure you:
1. Set `APP_ENV=production` and `APP_DEBUG=false`
2. Configure PostgreSQL database
3. Set up the scheduler cron job
4. Configure mail settings
5. Run migrations: `php artisan migrate --force`

## Project Structure

### Domain Layer (`app/Domain/`)

Contains pure business logic:
- `User` - User aggregate
- `Company` - Company entity with activation logic
- `JobPosting` - Job posting entity with tracking
- `Shared` - Shared value objects and base classes

### Application Layer (`app/Application/`)

Use case orchestration:
- `Actions/` - Command handlers for business operations
- `DTOs/` - Data transfer objects
- `Contracts/` - Interfaces and ports

### Infrastructure Layer (`app/Infrastructure/`)

External adapters:
- `Admin/Filament/` - Filament resources and pages
- `Console/` - Artisan commands
- `Mail/` - Email templates
- `Services/Workable/` - Workable API client

## API Documentation

API documentation can be generated with Scribe:

```bash
php artisan scribe:generate
```

Access at `/docs` after generation.

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'feat: add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Commit Convention

We follow [Conventional Commits](https://www.conventionalcommits.org/):

```
feat(scope): add new feature
fix(scope): fix bug
refactor(scope): refactor code
test(scope): add tests
docs(scope): update documentation
chore(scope): update dependencies
```

## License

This project is licensed under the MIT License.

## Support

For issues and questions:
- **Issues**: [GitHub Issues](https://github.com/danielebarbaro/ekswai-jobs-scraper/issues)
- **Email**: your-email@example.com
