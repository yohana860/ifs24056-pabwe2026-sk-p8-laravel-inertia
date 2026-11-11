# BlogVerse Pro — PABWE 2026 Praktikum 8 (Laravel 13 + Inertia.js + React)

Platform publikasi artikel multi-tier: **Publik (Guest)**, **Penulis (Member)**, dan **Admin** (monitoring read-only).

## Stack
Laravel 13 · PHP ≥ 8.4 · Inertia.js 2 + React 19 · PostgreSQL ≥ 17 · PhpSpreadsheet · OpenAI · TOAST UI Editor/Viewer · PHPUnit 12 · Tailwind CSS 4 · Vite.

## Menjalankan secara lokal
```bash
composer install            # menghasilkan composer.lock -> WAJIB di-commit
cp .env.example .env
php artisan key:generate
# buat database `pabwe_p8_blog` di PostgreSQL, sesuaikan DB_* di .env
php artisan migrate --seed
php artisan storage:link
npm ci --ignore-scripts && npm run build     # atau: npm run dev
php artisan serve
```
Akun seeder (username / password): `admin` / `admin` dan `member` / `member`.

Isi `OPENAI_API_KEY` di `.env` untuk AI Generator. Key hanya dibaca server (`config/openai.php`) dan tidak pernah dikirim ke browser.

Ekstensi PHP yang dibutuhkan: `pdo_pgsql`, `gd`, `zip`, `mbstring`, `xml`, `fileinfo`. Untuk coverage: `pcov` atau `xdebug`.

## Pengujian
```bash
composer test                  # php artisan test (seluruh Unit + Feature)
composer test:coverage         # php artisan test --coverage --min=100
composer audit
```
Pengujian memakai SQLite in-memory (lihat `phpunit.xml`). Logika query dijaga portabel (PostgreSQL & SQLite).

## Struktur (Feature-First)
```
app/Features/
  Admin/   Controllers, Services
  Ai/      Contracts (AiClientInterface), DTO, Exceptions, Services (OpenAiPostGeneratorService)
  Auth/    Actions, Controllers, Requests
  Post/    Contracts, Controllers, Enums (PostCategory), Models, Policies, Repositories, Requests, Services
  Profile/ Controllers, Requests, Services
app/Http/Middleware/   EnsureRole, EnsurePostOwnership, HandleInertiaRequests
resources/js/          Layouts, components, Pages/{Public,Auth,Post,Profile,Admin}
```
Keamanan akses: middleware `auth` + `role:admin` + `post.owner` (policy `PostPolicy::manage`) di backend; admin hanya punya rute GET (read-only).

## Jenkins
`Jenkinsfile` adalah file dari modul (tidak diubah). Pastikan **`composer.lock` dan `package-lock.json` ter-commit**. Direktori `storage/**` dan `bootstrap/cache` memiliki `.gitignore` (`*` + `!.gitignore`) agar tetap ada setelah clone sehingga `php artisan view:clear` tidak gagal.
