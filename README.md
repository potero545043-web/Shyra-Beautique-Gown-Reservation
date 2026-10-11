<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Vercel deployment

The Vercel adapter in `vercel.json` builds Vite assets into Laravel's `public` directory, serves those static files before routing Laravel requests through the PHP runtime, and exposes a server-side Vercel Blob bridge. Public gown and accessory images use a public Blob store. Government ID images use a separate private Blob store and are delivered only through the existing authenticated Laravel routes.

Before creating a deployment:

1. Import this repository into Vercel and use the `Other` framework preset.
2. Keep the existing Blob store private and create a second Blob store with public access. Connect both stores to the Vercel project, using the prefixes `PRIVATE_BLOB` and `PUBLIC_BLOB` for their generated store-ID variables. The SDK uses Vercel's automatically managed OIDC token; read-write tokens with the matching prefixes are only needed outside Vercel.
3. Add a strong, random `BLOB_BRIDGE_SECRET` as a Vercel environment variable. Never put Blob tokens, the bridge secret, database passwords, or `APP_KEY` in Git or chat.
4. Add the app environment variables in Vercel: `APP_ENV=production`, `APP_DEBUG=false`, a generated `APP_KEY`, `APP_URL`, `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT=4000`, `DB_DATABASE=gownrentalsystem`, `DB_USERNAME`, `DB_PASSWORD`, `SESSION_DRIVER=database`, `CACHE_STORE=database`, and `QUEUE_CONNECTION=database`. Generate an RSA key pair with `php artisan security:generate-login-password-keypair --base64` and save the printed `LOGIN_PASSWORD_PRIVATE_KEY_BASE64` value as a Vercel secret. Never commit or share the private key. The TiDB TLS root certificate is bundled at `certs/isrgrootx1.pem`; the MySQL connection verifies the server certificate using it by default on Vercel.
5. Deploy to Preview first. Back up the intended TiDB database, then run `php artisan migrate --force` once from a secure deployment environment. Do not add migrations to the build command.

For local development, run `php artisan security:generate-login-password-keypair` once. Password forms encrypt their values in the browser before submitting; the server decrypts them before normal validation and authentication. When a signed-in user follows an external link, the app asks for confirmation and logs them out before continuing. Closing the tab or navigating away through the browser controls also prompts for confirmation and attempts to log out. HTTPS is still required in production because client-side payload encryption does not replace TLS.

The Blob bridge accepts only validated image uploads up to 4 MB, requires a server-only shared secret, and restricts private-file reads to private Blob URLs. Storage credentials must be configured in Vercel before testing uploads. Vercel's function request-size limits still apply, so larger files must be reduced before upload.

## Railway deployment

1. Connect this GitHub repository to a Railway service. Railway detects the root `Dockerfile`; keep the service on a single replica when using the local-file volume described below.
2. Add a Railway MySQL service. In the app service, set `DB_CONNECTION=mysql` and reference the database service's `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, and `MYSQLPASSWORD` variables. For a database service named `MySQL`, for example, use `${{MySQL.MYSQLHOST}}` for `DB_HOST`.
3. Add production variables to the app service: `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` generated with `php artisan key:generate --show`, `APP_URL` set to the Railway HTTPS domain, `APP_TIMEZONE=Asia/Manila`, `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database`, `FILESYSTEM_DISK=local`, and `LOG_CHANNEL=stderr`. Keep keys and database credentials out of Git.
4. Set `LOGIN_PASSWORD_PRIVATE_KEY_BASE64` as a sealed Railway variable. In PowerShell, generate a production-only key without replacing the local development key:

   ```powershell
   $env:LOGIN_PASSWORD_PRIVATE_KEY_PATH = "$env:TEMP\railway-login-password-private.pem"
   php artisan security:generate-login-password-keypair --base64
   Remove-Item Env:LOGIN_PASSWORD_PRIVATE_KEY_PATH
   ```

   Copy the printed base64 value directly into Railway's variable editor, then delete `$env:TEMP\railway-login-password-private.pem`. Never paste the key into Git or chat.
5. Attach a Railway volume to the app service at `/var/www/html/storage/app`. This preserves public uploads and private ID/payment files across deployments. The Docker image creates the public storage link and fixes volume permissions at startup.
6. Set the app service's pre-deploy command to `php artisan migrate --force`, then deploy. Do not run `php artisan db:seed` in production: the default seeder creates demonstration accounts with known passwords. Create the production owner account separately with a unique password.
7. Generate the app's public domain in Railway and verify that `APP_URL` matches it. Confirm the deployment logs show the detected Dockerfile and that migrations complete successfully.
8. To enable the scheduled reservation reminders, add a second service from this repository with the same app/database variables, start command `php artisan schedule:run`, and Cron Schedule `*/5 * * * *`. Railway cron jobs run in UTC and must be at least five minutes apart; the app timezone keeps its daily reminder schedule on Philippine time.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
