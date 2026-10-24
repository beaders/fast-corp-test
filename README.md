# Fast Corporation test

Laravel 13 application for the Senior Software Engineer assignment, running on Nginx, PHP-FPM, MySQL, and Redis.

## Requirements

- Docker Engine or Docker Desktop with Docker Compose v2 or newer.
- Bash (available by default on macOS and most Linux distributions).

## Start, stop, and test

```sh
./start.sh
./test.sh
./stop.sh
```

Open [http://localhost:8080](http://localhost:8080) for the Laravel welcome page. The application health endpoint is [http://localhost:8080/up](http://localhost:8080/up).

`start.sh` creates `.env` if missing, builds the PHP image, installs the locked Composer dependencies, clears cached configuration, generates `APP_KEY` only when missing, starts MySQL/Redis/PHP, runs pending migrations, and starts Nginx. Run it again to apply dependency, image, or migration changes. Existing keys and data are preserved.

`stop.sh` removes the containers and network while keeping both data volumes.

`test.sh` runs the Composer test script in a temporary PHP container and removes it afterward. It clears cached configuration first and forwards options to Laravel's test runner. Run `start.sh` once to prepare the environment; after that, tests also work while the application is stopped. The default PHPUnit configuration uses SQLite in memory and array cache/session drivers.

```sh
./test.sh --filter=test_the_application_returns_a_successful_response
./test.sh --testsuite=Feature
```

All three scripts work from another directory when called by their path, and return a nonzero exit status on failure.

## Services

| Service | Image | Access |
| --- | --- | --- |
| Nginx | `nginx:1.30-alpine` | `http://localhost:8080` |
| PHP-FPM | `php:8.4-fpm-alpine`, with Composer 2, `pdo_mysql`, `pcntl`, and `redis` | Internal port `9000` |
| MySQL | `mysql:8.4` | `127.0.0.1:3306` |
| Redis | `redis:8-alpine`, with append-only persistence | Internal port `6379` |

Nginx serves `public/` and forwards requests to Laravel's `public/index.php` through PHP-FPM. Source files are mounted into the PHP container, so code changes take effect without rebuilding. PHP waits for healthy MySQL and Redis services; Nginx waits for PHP-FPM and checks Laravel's `/up` endpoint.

Laravel reads its configuration from `.env`. It connects to `mysql:3306` for the database and `redis:6379` for cache and sessions. Queued jobs use the database driver; start a worker with `docker compose exec php php artisan queue:work` when needed.

Configure host ports and local credentials in `.env`. If you change `APP_PORT`, also update `APP_URL`. Host database clients use `127.0.0.1` and `MYSQL_PORT`. Published ports bind to the local machine only; Redis is available inside the Compose network. The example passwords and debug settings are intended for local development.

MySQL and Redis data are stored in the `mysql_data` and `redis_data` named volumes and survive `docker compose down`. MySQL credentials are initialized on the first startup with an empty volume; changing them in `.env` does not update an existing database.

The PHP entrypoint prepares writable `storage/` and `bootstrap/cache` directories for PHP-FPM. Application dependencies are installed into the mounted `vendor/` directory and pinned by `composer.lock`.

## Useful commands

```sh
# Run Laravel commands or install PHP dependencies
docker compose exec php php artisan about
docker compose exec php composer install

# Run the test suite
./test.sh

# Check service status or follow logs
docker compose ps
docker compose logs -f

# Open the application database (enter DB_PASSWORD when prompted)
docker compose exec mysql sh -c 'exec mysql -u "$MYSQL_USER" -p "$MYSQL_DATABASE"'

# Open the Redis CLI
docker compose exec redis redis-cli

# Stop containers while keeping data
./stop.sh
```

The default welcome page works without Node.js. For frontend development, install Node.js locally and run `npm install` followed by `npm run build`; the PHP container does not include Node.js.

To remove both local data volumes, run `docker compose down -v`. This deletes the database and Redis data.

Documentation: [Laravel installation](https://laravel.com/docs/13.x/installation), [Laravel Redis configuration](https://laravel.com/docs/13.x/redis), and official images for [Nginx](https://hub.docker.com/_/nginx), [PHP](https://hub.docker.com/_/php), [MySQL](https://hub.docker.com/_/mysql), and [Redis](https://hub.docker.com/_/redis).
