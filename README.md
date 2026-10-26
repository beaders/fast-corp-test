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

Open [http://localhost:8080](http://localhost:8080) to sign in and access Page A. The root URL redirects signed-in users to `/page-a`. The application health endpoint is [http://localhost:8080/up](http://localhost:8080/up).

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

The `user` object contains `id`, `name`, `email`, and `role`. Registration immediately signs in the new user with the `user` role; no email confirmation is required or sent. Successful web login and registration redirect to `/page-a`. Email addresses are trimmed and lowercased. Registration passwords must have at least 8 characters, fit within bcrypt's 72-byte limit, contain no null bytes, and match `password_confirmation`.

`AuthMiddleware` protects `/api/me` and `/api/logout` using the `web` session guard. Guests receive a JSON `401` response, including requests without an `Accept` header. Reading `/api/me` does not create an activity event.

`AdminMiddleware` is registered as `admin`. Apply `->middleware('admin')` to routes that require the `admin` role. It checks the `web` session guard and returns JSON `403` for other roles and guests.

First request `GET /` to receive the session and `XSRF-TOKEN` cookies. Send the session cookie on subsequent requests and the URL-decoded `XSRF-TOKEN` cookie value as the `X-XSRF-TOKEN` header on POST requests. Login and registration rotate the session and CSRF token, so use the latest cookies for the next request.

Invalid input or credentials return `422` with JSON validation errors; unauthenticated requests to `/api/me` or `/api/logout` return `401`, and POST requests with missing or invalid CSRF tokens return `419`. Login and registration each allow 10 requests per minute per guest IP address or authenticated user and return `429` when exceeded. Successful registration, login, and logout create authentication records in `activity_events`.

The shared abstract `Controller` records `view-page` events when `PagesController` serves the Page A or Page B view, using the signed-in user's ID. `AuthController` uses the same logic for the login and registration forms, with `user_id` set to `null`. Their actions are `login` and `registration`; the protected pages use `page-a` and `page-b`. Redirects and API responses do not create page-view events.

## Button actions

These endpoints require an authenticated session and a valid CSRF token:

| Method | Endpoint | Response |
| --- | --- | --- |
| POST | `/api/buy-cow` | `204`; Page A then displays `thankYou` |
| POST | `/api/download` | `200` with the `sample.exe` attachment |

Each successful request creates one `button-click` record in `activity_events`, with the action (`buy-cow` or `download`), signed-in user's ID, and server timestamp. Page A prevents repeated clicks while its request is pending and lets the user retry if the request fails. Page B submits directly to the download API so the browser downloads the file. The former `GET /download` endpoint has been replaced by `POST /api/download`.

## Statistics and reports

All signed-in accounts can open Reports at `/page-reports` through the shared navigation. Statistics at `/page-stats` requires the `admin` role; regular users receive `403` and do not see its navigation link. Guests are redirected to login for both pages. Each page records its own `view-page` events.

Statistics lists events from newest to oldest, with combinable date, user, and action filters. Actions are `login`, `logout`, `registration`, `view-page`, and `button-click`. Authentication actions exclude visits to the login and registration forms. The user filter includes guest/deleted-user events. Results contain 25 events per page and retain their filters and event snapshot during pagination, so new events do not repeat rows between pages.

Reports shows a daily line chart and matching table for Page A views, Page B views, Buy a cow clicks, and Download clicks. Days without activity have zero counts. Totals and the chart cover the complete selected range; the table contains 31 days per page. Dates use the application timezone (UTC by default).

Reports accepts optional inclusive `from` and `to` dates, with at most 366 days in one report. Without dates, it shows the available activity up to the most recent 366 days. With only `from`, it shows up to 366 days beginning there; with only `to`, it shows up to 366 days ending there. Earlier activity remains accessible by selecting another range. The chart is rendered as SVG without an external chart service or frontend build step.
