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

## Authentication

Authentication uses Laravel sessions and cookies. These JSON endpoints run through the web middleware, including CSRF protection:

| Method | Endpoint | Request | Response |
| --- | --- | --- | --- |
| POST | `/api/register` | `name`, `email`, `password`, `password_confirmation` | `201` with `user` |
| POST | `/api/login` | `email`, `password` | `200` with `user` |
| GET | `/api/me` | Authenticated session | `200` with `user` |
| POST | `/api/logout` | Authenticated session | `204` |

The `user` object contains `id`, `name`, `email`, and `role`. Registration immediately signs in the new user with the `user` role; no email confirmation is required or sent. Email addresses are trimmed and lowercased. Registration passwords must have at least 8 characters, fit within bcrypt's 72-byte limit, contain no null bytes, and match `password_confirmation`.

`AuthMiddleware` protects `/api/me` and `/api/logout` using the `web` session guard. Guests receive a JSON `401` response, including requests without an `Accept` header. Reading `/api/me` does not create an activity event.

`AdminMiddleware` is registered as `admin`. Apply `->middleware('admin')` to routes that require the `admin` role. It checks the `web` session guard and returns JSON `403` for other roles and guests.

First request `GET /` to receive the session and `XSRF-TOKEN` cookies. Send the session cookie on subsequent requests and the URL-decoded `XSRF-TOKEN` cookie value as the `X-XSRF-TOKEN` header on POST requests. Login and registration rotate the session and CSRF token, so use the latest cookies for the next request.

Invalid input or credentials return `422` with JSON validation errors; unauthenticated requests to `/api/me` or `/api/logout` return `401`, and POST requests with missing or invalid CSRF tokens return `419`. Login and registration each allow 10 requests per minute per guest IP address or authenticated user and return `429` when exceeded. Successful registration, login, and logout create authentication records in `activity_events`.
