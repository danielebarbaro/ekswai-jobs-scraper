# ekswai

<p align="center">
  <img src="public/images/hero.png" alt="ekswai" width="480">
</p>

<p align="center">
  Track job postings from multiple job boards, manage your application pipeline.
</p>

<p align="center">
  <a href="https://github.com/danielebarbaro/ekswai-jobs-scraper/actions/workflows/tests.yml"><img src="https://github.com/danielebarbaro/ekswai-jobs-scraper/actions/workflows/tests.yml/badge.svg" alt="Tests"></a>
  <a href="https://github.com/danielebarbaro/ekswai-jobs-scraper/actions/workflows/analyse.yml"><img src="https://github.com/danielebarbaro/ekswai-jobs-scraper/actions/workflows/analyse.yml/badge.svg" alt="Static Analysis"></a>
  <a href="https://github.com/danielebarbaro/ekswai-jobs-scraper/actions/workflows/lint.yml"><img src="https://github.com/danielebarbaro/ekswai-jobs-scraper/actions/workflows/lint.yml/badge.svg" alt="Linter"></a>
</p>

## What it does

ekswai lets you follow companies across job boards and track their job postings. You add companies by their provider slug, the system validates them against the provider API and syncs new positions every day. You get email notifications for companies you care about, and you manage your entire application pipeline from one dashboard.

Currently supported: **Workable**. The architecture is provider-agnostic, so adding new job boards is straightforward (see [Adding a provider](#adding-a-provider)).

**For users:**
1. Register and go to My Companies
2. Add a company slug (e.g. `laravel` from apply.workable.com/laravel for Workable)
3. The system validates and syncs job postings automatically
4. Browse your dashboard: bookmark, mark as submitted, track interviews, dismiss

**For admins:**
Filament 4 panel to manage companies, job postings, and users.

## Tech Stack

| Component | Technology |
|-----------|------------|
| Framework | Laravel 12 (PHP 8.4) |
| Frontend | React 19, Inertia.js, TypeScript, Tailwind CSS 4 |
| Database | PostgreSQL (SQLite for tests) |
| Admin | Filament 4 |
| Testing | Pest |
| Static Analysis | PHPStan (Larastan) level 5 |
| Code Style | Laravel Pint |
| CI/CD | GitHub Actions |
| Local Dev | Laravel Sail (Docker) |

## Architecture

DDD with three layers:

```
app/
├── Domain/              # Models, business logic
│   ├── Company/         # Company entity (global, shared between users)
│   │   └── JobBoardProvider.php  # Enum of supported providers
│   ├── JobPosting/      # Job posting entity with per-user status
│   ├── Shared/          # BaseModel (UUIDs, guarded)
│   └── User/            # User with subscriptions and job statuses
├── Application/         # Use cases
│   ├── Actions/         # FollowCompany, UnfollowCompany, Sync, Notify
│   └── DTOs/            # JobPostingDTO
└── Infrastructure/      # External adapters
    ├── Admin/Filament/  # Admin panel resources
    ├── Console/         # Artisan commands (jobs:sync-daily)
    ├── Mail/            # Email templates
    └── Services/
        ├── Contracts/   # JobBoardClient interface
        ├── Workable/    # Workable API client
        └── JobBoardClientFactory.php
```

**Provider pattern:** each job board integration implements the `JobBoardClient` interface, which defines two methods: `fetchJobsForCompany(string $slug)` and `validateSlug(string $slug)`. The `JobBoardProvider` enum lists available providers, and `JobBoardClientFactory` resolves the correct client implementation based on the provider.

**Key relationships:**
Users subscribe to companies via `company_user` pivot (with email notification toggle). Each user has a per-job status via `job_posting_user` pivot (new, bookmarked, submitted, interview, dismissed). The sync is shared: one API call per company regardless of subscriber count.

### Adding a provider

1. Add a new case to `JobBoardProvider` enum (`app/Domain/Company/JobBoardProvider.php`)
2. Create a class implementing the `JobBoardClient` interface (`app/Infrastructure/Services/Contracts/JobBoardClient.php`)
3. Register the new class in `JobBoardClientFactory::make()` (`app/Infrastructure/Services/JobBoardClientFactory.php`)

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

## Commands

```bash
# Sync job postings from all active companies
./vendor/bin/sail artisan jobs:sync-daily

# Run tests
composer test

# Code style
composer lint

# Static analysis
composer analyse
```

The sync runs automatically every day at 9:00 AM UTC via Laravel scheduler.

## License

MIT
