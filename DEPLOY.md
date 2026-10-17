# Deploying the Faculty Class Record System

Steps for the ESSU Guiuan server. Run them in order. Commands are run from the project folder.

## 1. Server requirements

- PHP 8.4 with the extensions `pdo_mysql`, `mbstring`, `xml`, `zip`, `gd`, `bcmath`, `fileinfo`
- MySQL 8 (or MariaDB 10.6+)
- Composer 2 and Node.js 18+ (only for building the CSS / JS)
- HTTPS certificate for the site address

## 2. Before every deploy

1. **Back up the database** (Admin → Backup & Restore → Run Backup Now, then download the file), or:
   ```bash
   mysqldump -u <user> -p <database> > backup-$(date +%Y%m%d-%H%M).sql
   ```
2. Tell users the system will be unavailable for a few minutes.

## 3. First install only

```bash
git clone https://github.com/gitpushfrances/class-record-system.git
cd class-record-system
cp .env.example .env
php artisan key:generate
php artisan storage:link
```

Edit `.env`:

| Key | Value |
|---|---|
| `APP_NAME` | `"Class Record System"` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` (never `true` on the server: it shows passwords and paths on error pages) |
| `APP_URL` | `https://<site address>` |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | the server database |
| `MAIL_*` | the school mail account (password reset e-mails) |

**Do not run `php artisan db:seed` on the server.** The full seeder creates sample departments, students, sections and sample accounts whose password is `password`. Run only the roles seeder, then create the real Admin account:

```bash
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
php artisan tinker --execute="App\Models\User::create(['name' => 'System Administrator', 'last_name' => 'Administrator', 'first_name' => 'System', 'email' => 'admin@essu.edu.ph', 'password' => bcrypt('CHANGE-ME-NOW'), 'role' => 'super_admin', 'status' => 'active'])->assignRole('super_admin');"
```

Sign in as that Admin, change the password (Profile), then add departments, programs, the academic period, ROG settings and the Dean accounts from the Admin pages.

## 4. Every deploy (updates)

```bash
php artisan down
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```

After the first deploy of the grading changes (Phase 2), recompute stored grades once:

```bash
php artisan grades:recompute          # dry run: lists what would change
php artisan grades:recompute --apply  # writes the new values
```

## 5. Backups

Backups are **not scheduled**: they run only when the Admin clicks **Run Backup Now**. Make it a routine (for example every Friday, and before and after each grading period). To automate it later, add `$schedule->command('backup:run')->dailyAt('01:00');` to `app/Console/Kernel.php` and this cron entry on the server:

```
* * * * * cd /path/to/class-record-system && php artisan schedule:run >> /dev/null 2>&1
```

## 6. Smoke test after deploy

Sign in once as each role and open every sidebar link:

- **Admin**: Departments, Academic Period, ROG Settings, View As → Dean, then Back to Admin
- **Dean**: Faculty, Sections, Students, Subjects, Promotion
- **Program Head**: Sections (open one, assign a subject), Students (Import → Template), Subjects
- **Teacher**: a Class Record, Attendance, Final Grades, Report of Grades (Print)

Every page should open without an error. If one fails, `storage/logs/laravel.log` has the reason.

## 7. Rolling back

```bash
php artisan down
git checkout <previous commit or tag>
composer install --no-dev --optimize-autoloader && npm ci && npm run build
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```

If the failed deploy ran migrations, restore the database from the backup taken in step 2 instead of rolling migrations back by hand.
