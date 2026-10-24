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
