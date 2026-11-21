# SPACES

A Laravel-based marketplace for discovering and booking photography studios, creative services, and session packages in Jordan.

Built as an Orange Coding Academy team project.

## Overview

SPACES brings three workflows together:

- **Customers:** discover spaces and packages, make bookings, manage favorites, and submit reviews.
- **Providers:** manage their spaces, services, packages, availability, bookings, profile, and reviews.
- **Administrators:** oversee users, partners, catalog approvals, operations, traffic analytics, and AI-assisted administration.

This README documents the uploaded project source. The presence of a route or screen does not mean its complete workflow has been tested.

## Technology

| Area | Technology |
| --- | --- |
| Backend | PHP 8.2+, Laravel 12 |
| Templates | Laravel Blade |
| Frontend | JavaScript, CSS, Bootstrap-related dependencies, Tailwind-related dependencies |
| Asset tooling | Vite 7 |
| Database | MySQL for the team's local setup |
| Authentication | Laravel UI and Laravel Socialite for Google sign-in |
| AI | Gemini API through Laravel's HTTP client |
| Testing | PHPUnit 11 |

Some interfaces use assets directly from `public/css` and `public/js`; others may rely on compiled assets. Keep both asset paths in mind when troubleshooting styling or scripts.

## Main features

### Customer experience

- Landing page and studio browsing.
- Public package catalog and detail pages.
- Service detail and booking interfaces.
- Booking history and booking details.
- Favorites and reviews.
- Account authentication and optional Google sign-in.

### Provider workspace

- Dashboard and booking management.
- Space management, including photos and cover selection.
- Service and package management.
- Package photos and included services.
- Opening hours and closed periods.
- Profile management and review listings.
- **Write with AI** for space, service, and package descriptions.

### Admin workspace

- Dashboard and user management.
- Partner review and catalog approval workflows.
- Category management.
- Booking, transaction, and review oversight.
- Traffic analytics.
- CSV export and selected import tools.
- AI chat and briefing endpoints.

Transaction-related screens and models do not, by themselves, establish that a live payment gateway is configured.

## Local requirements

- PHP 8.2 or later, with Laravel's required extensions and a MySQL PDO driver.
- Composer.
- Node.js compatible with Vite 7; Node.js 22.12+ is a suitable baseline.
- npm.
- MySQL.
- Git.

The team uses Windows, VS Code, PowerShell, and XAMPP. Laravel can be served with Artisan while XAMPP supplies MySQL.

## First-time setup on Windows

### 1. Clone and enter the project

```powershell
git clone https://github.com/Mousa-Joudeh/laravel-project.git
cd laravel-project
```

If you already have a checkout, use it rather than cloning over existing work.

### 2. Install dependencies

```powershell
composer install
npm ci
```

Use `npm install` instead if your checkout does not contain `package-lock.json`. Do not delete or regenerate lock files merely to bypass an installation failure.

### 3. Create your local environment file

Only if `.env` does not already exist:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Do not replace an existing `.env` or regenerate an established application's encryption key unnecessarily.

### 4. Configure MySQL

Start MySQL in XAMPP. Create an empty database named `space` using phpMyAdmin or your database client.

In your local `.env`, set:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=space
```

Set `DB_USERNAME` and `DB_PASSWORD` privately to match your local database account. Remove or update any conflicting `DB_URL` configuration.

### 5. Apply the existing schema

Verify that `.env` targets the intended local development database, then run:

```powershell
php artisan config:clear
php artisan migrate
php artisan storage:link
```

**Keep the team's migration schema unchanged.** Do not run `migrate:fresh` or `migrate:refresh` against a database containing data you need.

The default `DatabaseSeeder` creates a test user; it is not a complete studio catalog or an admin/provider provisioning process.

### 6. Start the application

Terminal 1:

```powershell
php artisan serve
```

Terminal 2:

```powershell
npm run dev
```

Open:

```text
http://127.0.0.1:8000
```

For a compiled frontend build:

```powershell
npm run build
```

## Access and important URLs

| Screen | URL path | Access |
| --- | --- | --- |
| Home | `/` | Public |
| Login | `/login` | Guest authentication flow |
| Register | `/register` | Guest registration flow |
| Studio catalog | `/studios` | Subject to controller rules |
| Package catalog | `/packages` | Public listing routes |
| Provider dashboard | `/provider/dashboard` | Authenticated, active provider |
| Admin dashboard | `/admin` | Authenticated, active admin |

The provider entry point is `/provider/dashboard`, not `/provider`.

The `EnsureProvider` middleware requires `role = provider` and `status = active`. The `EnsureAdmin` middleware requires `role = admin` and `status = active`.

Use the team's authorized account-management workflow to provision these accounts. Do not remove access checks to make a dashboard accessible.

## Gemini configuration

Every developer who wants to use AI locally must configure their own local environment. End users of a deployed site do not need to supply an API key.

The application reads:

- `GEMINI_API_KEY`: private API key.
- `GEMINI_MODEL`: model identifier available to your Google project.

Keep an empty key entry in `.env.example` and put the real key only in your private `.env` or deployment secret manager.

The model used in the recent local integration test was:

```dotenv
GEMINI_MODEL=gemini-3.1-flash-lite
```

The source fallback in `config/services.php` differs from that explicit setting. Set a non-empty model identifier deliberately; an empty environment value does not use the fallback.

After configuring your environment:

```powershell
php artisan config:clear
```

Check configuration without printing the key:

```powershell
php artisan tinker
```

```php
dump([
    'key_configured' => filled(config('services.gemini.key')),
    'model' => config('services.gemini.model'),
]);
```

Provider AI flow:

```text
Blade form: Write with AI
  -> public/js/spaces.js
  -> POST /provider/ai/describe
  -> Provider/AIController
  -> GeminiService
  -> Gemini API
  -> description textarea
```

The provider endpoint requires authentication and provider access, validates the submitted form context, and is rate limited. Treat generated text as a draft: review it before saving and do not invent facilities, equipment, or prices.

Requests send the supplied context to Google and may consume billable API quota. Avoid including sensitive customer information.

## Optional Google sign-in

Configure `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and `GOOGLE_REDIRECT_URI` privately. Register the corresponding redirect URI in the Google application.

The local callback route is:

```text
http://127.0.0.1:8000/auth/google/callback
```

Do not expose the client secret in Blade templates, browser JavaScript, or Git history.

## Project structure

```text
app/
  Http/Controllers/
    Admin/                  Admin workspace
    Provider/               Provider workspace
    Auth/                   Authentication and Google sign-in
  Http/Middleware/           Access restrictions and request middleware
  Models/                   Eloquent models
  Services/                 Gemini integration and other service logic
bootstrap/app.php           Application setup and feature-route loading
config/services.php         Third-party service configuration
database/
  migrations/               Team-owned database schema
  factories/
  seeders/
public/
  css/                      Directly served styles
  js/                       Directly served scripts
  images/
resources/views/
  admin/
  provider/panel/
  customer/
  layouts/
routes/                     Feature-specific route files
tests/
```

Feature route files are loaded through `bootstrap/app.php`. A route file must be registered there to become available.

## Updating your local branch

Start with:

```powershell
git status
```

Commit or otherwise safely preserve local changes before integrating remote updates. For the team's `abeer` branch:

```powershell
git switch abeer
git fetch origin
git merge origin/main
composer install
npm ci
php artisan optimize:clear
php artisan migrate
```

Resolve any merge conflicts before continuing. Review new migrations before applying them to the correct local database.

## Team contribution workflow

1. Work on your assigned branch, not directly on `main`.
2. Make focused changes and review `git diff`.
3. Stage explicit file paths.
4. Commit related changes with a descriptive message.
5. Push your branch and open a pull request targeting `main`.
6. Have the designated reviewer review and merge.

Example:

```powershell
git add public/js/spaces.js
git diff --cached --stat
git commit -m "fix: preserve AI descriptions when generation fails"
git push origin abeer
```

Coordinate edits to shared files such as `bootstrap/app.php`, layouts, `config/services.php`, and shared JavaScript. Do not change migrations without team agreement.

Never commit `.env`, credentials, database exports containing private information, `vendor`, or `node_modules`.

## Verification

List registered routes:

```powershell
php artisan route:list
php artisan route:list --path=provider -v
php artisan route:list --path=admin -v
```

Run the existing automated suite:

```powershell
composer test
```

The supplied PHPUnit configuration uses in-memory SQLite. Ensure the SQLite PHP extension is enabled. Existing test files include example tests and admin-AI unit tests; this does not establish full CRUD or end-to-end coverage.

Suggested manual smoke checks:

- Login and verify role-based dashboard access.
- Create, update, and delete disposable records in a development database.
- Verify a provider cannot edit another provider's records.
- Check image upload and display.
- Check weekly availability and blocked periods.
- Generate descriptions in all three provider forms.
- Verify failed AI requests leave the existing description intact.
- Check customer booking and admin approval flows.

This README was prepared from source inspection; the complete application and automated suite were not executed while writing it.

## Troubleshooting

| Symptom | Check |
| --- | --- |
| `403` on a dashboard | Signed-in account's role and active status |
| `404` at `/provider` | Open `/provider/dashboard` instead |
| Missing Blade view | Controller view name, file path, and feature implementation |
| Missing CSS or JavaScript | Browser network panel, public assets, and Vite/build output |
| Uploaded images not visible | Public storage link, disk configuration, and stored path |
| Database connection error | MySQL service and private `.env` configuration |
| Gemini configuration missing | Key presence and non-empty model, then clear config cache |
| AI request fails | Server logs, quota, model access, and network connectivity |
| `Auth::routes()` requires Laravel UI | Run `composer install` using the committed dependency files |
| `gh` is not recognized | Use GitHub's web interface or install GitHub CLI separately |

Review logs privately in `storage/logs`. Never share unredacted environment files, tokens, or logs containing credentials.

## Production considerations

Configure the hosting environment separately, use HTTPS, disable debug output, keep secrets server-side, build assets, and apply reviewed migrations with backups. Validate queue, mail, storage, logging, and role provisioning for the actual deployment.

Local testing does not establish production readiness or successful live payment processing.

