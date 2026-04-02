# ekswai

<p align="center">
  <img src="public/images/hero.png" alt="ekswai" width="480">
</p>

<p align="center">
  Multi-provider job board aggregator. Follow companies, get notified of new positions daily, and manage your application pipeline.
</p>

<p align="center">
  <a href="https://github.com/danielebarbaro/ekswai-jobs-scraper/actions/workflows/tests.yml"><img src="https://github.com/danielebarbaro/ekswai-jobs-scraper/actions/workflows/tests.yml/badge.svg" alt="Tests"></a>
  <a href="https://github.com/danielebarbaro/ekswai-jobs-scraper/actions/workflows/analyse.yml"><img src="https://github.com/danielebarbaro/ekswai-jobs-scraper/actions/workflows/analyse.yml/badge.svg" alt="Static Analysis"></a>
  <a href="https://github.com/danielebarbaro/ekswai-jobs-scraper/actions/workflows/lint.yml"><img src="https://github.com/danielebarbaro/ekswai-jobs-scraper/actions/workflows/lint.yml/badge.svg" alt="Linter"></a>
</p>

## What it does

ekswai lets you follow companies across job boards and track their job postings. You add companies by their provider slug, the system validates them against the provider and syncs new positions every day. You get email notifications for companies you care about, and you manage your entire application pipeline from one dashboard.

**For users:**
1. Register and go to My Companies
2. Add a company slug (e.g. `laravel` from apply.workable.com/laravel for Workable)
3. The system validates and syncs job postings automatically
4. Browse your dashboard: bookmark, mark as submitted, track interviews, dismiss

**For admins:**
Filament 4 panel to manage companies, job postings, users, and scraper configurations.

## Supported Providers

| Provider | Integration | Example URL |
|----------|-------------|-------------|
| Workable | REST API | `apply.workable.com/laravel` |
| Lever | REST API | `jobs.lever.co/scaleway` |
| Ashby | REST API | `jobs.ashbyhq.com/ramp` |
| Greenhouse | REST API | `boards.greenhouse.io/discord` |
| Teamtailor | HTML Scraper | `weroad.teamtailor.com/jobs` |
| Factorial | HTML Scraper | `shippypro.factorialhr.com` |

API providers use JSON endpoints directly. HTML scraper providers use configurable CSS selectors with built in health checks and retry logic (managed via the admin panel).

## Tech Stack

| Component | Technology |
|-----------|------------|
| Framework | Laravel 12 (PHP 8.4) |
| Frontend | React 19, Inertia.js, TypeScript, Tailwind CSS 4 |
| Database | PostgreSQL (SQLite for tests) |
| Admin | Filament 4 |
| Testing | Pest |
| Static Analysis | PHPStan (Larastan) level 6 |
| Code Style | Laravel Pint |
| Refactoring | Rector (PHP 8.4, code quality, dead code, early return, type declarations) |
| CI/CD | GitHub Actions |
| Local Dev | Laravel Sail (Docker) |

## Architecture

DDD with three layers:

```
app/
├── Domain/
│   ├── Company/         # Company entity, JobBoardProvider enum
│   ├── JobPosting/      # Job posting with per-user status tracking
│   ├── ScraperConfig/   # CSS selectors and health check config for HTML scrapers
│   ├── Shared/          # BaseModel (UUIDs, $guarded = [])
│   └── User/            # User with company subscriptions and job statuses
├── Application/
│   ├── Actions/
│   │   ├── Company/     # FollowCompany, UnfollowCompany
│   │   ├── JobPosting/  # SyncCompanyJobPostings, UpdateJobPostingStatus
│   │   ├── Notification/# NotifyUserOfNewJobs
│   │   └── Sync/        # RunDailySync (orchestrator)
│   └── DTOs/            # JobPostingDTO
└── Infrastructure/
    ├── Admin/Filament/  # Admin panel (Companies, Jobs, Users, ScraperConfigs)
    ├── Console/         # Artisan commands (jobs:sync-daily)
    ├── Mail/            # NewJobsFoundMail, ScraperHealthAlertMail
    └── Services/
        ├── Contracts/   # JobBoardClient interface
        ├── Workable/    # Workable API client
        ├── Lever/       # Lever API client
        ├── Ashby/       # Ashby API client
        ├── Greenhouse/  # Greenhouse API client
        ├── Teamtailor/  # Teamtailor HTML scraper
        ├── Factorial/   # Factorial HTML scraper
        ├── Scraping/    # BaseHtmlScraper, ScraperHealthChecker, exceptions
        └── JobBoardClientFactory.php
```

**Provider pattern:** each job board integration implements the `JobBoardClient` interface (`fetchJobsForCompany` and `validateSlug`). The `JobBoardProvider` enum lists available providers, and `JobBoardClientFactory` resolves the correct client. API providers (Workable, Lever, Ashby, Greenhouse) call JSON endpoints directly. HTML scraper providers (Teamtailor, Factorial) extend `BaseHtmlScraper` which handles retry logic, DOM parsing, and validation using CSS selectors from `ScraperConfig`.

**Key relationships:**
Users subscribe to companies via `company_user` pivot (with email notification toggle). Each user has a per-job status via `job_posting_user` pivot (new, bookmarked, submitted, interview, dismissed). The sync is shared: one API/scrape call per company regardless of subscriber count.

### Adding a provider

1. Add a new case to `JobBoardProvider` enum (`app/Domain/Company/JobBoardProvider.php`)
2. Create a class implementing `JobBoardClient` (`app/Infrastructure/Services/Contracts/JobBoardClient.php`)
3. Register it in `JobBoardClientFactory::make()` (`app/Infrastructure/Services/JobBoardClientFactory.php`)
4. For HTML scraper providers: add a `ScraperConfig` entry with CSS selectors in `ScraperConfigSeeder`

## Quick Start

```bash
git clone https://github.com/danielebarbaro/ekswai-jobs-scraper.git
cd ekswai-jobs-scraper
composer install
cp .env.example .env
php artisan key:generate
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --seed
npm install && npm run build
```

Open http://localhost and register. Then go to /companies and add a company by selecting a provider and entering its slug.

The seeder creates an admin user (`me@plincode.tech` / `password`) with demo companies across all 6 providers, already synced with real job postings.

## Commands

```bash
# Sync job postings from all active companies
./vendor/bin/sail artisan jobs:sync-daily

# Run tests
composer test

# Code style (fix)
composer lint

# Static analysis
composer analyse

# Rector refactoring
composer rector          # Apply changes
composer rector-dry      # Preview changes

# Run analyse + test together
composer check
```

The sync runs automatically every day at 9:00 AM UTC via Laravel scheduler.

## License

MIT
