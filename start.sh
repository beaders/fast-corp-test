#!/usr/bin/env bash
set -euo pipefail

source "$(dirname -- "${BASH_SOURCE[0]}")/scripts/common.sh"

if [[ ! -f .env ]]; then
    cp .env.example .env
    printf 'Created .env from .env.example.\n'
fi

docker compose config --quiet
docker compose build php

docker compose run --rm --no-deps -T php sh -eu -c '
    composer install --no-interaction --prefer-dist --no-progress
    php artisan config:clear --no-interaction
'

docker compose run --rm --no-deps -T php php <<'PHP'
<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

if (empty(config('app.key'))) {
    $path = $app->environmentFilePath();
    $contents = preg_replace('/^[\t ]*(?:export[\t ]+)?APP_KEY[\t ]*=.*$/m', 'APP_KEY=', file_get_contents($path), -1, $count);
    if ($count === 0) {
        $contents = rtrim($contents)."\nAPP_KEY=\n";
    }
    if (file_put_contents($path, $contents) === false) {
        throw new RuntimeException('Unable to prepare APP_KEY in .env.');
    }

    $status = $kernel->call('key:generate', ['--no-interaction' => true]);
    echo $kernel->output();
    $values = Dotenv\Dotenv::parse(file_get_contents($path));
    if ($status !== 0 || empty($values['APP_KEY'])) {
        throw new RuntimeException('Unable to generate APP_KEY.');
    }
    exit($status);
}
PHP

docker compose up -d --wait --wait-timeout 120 php
docker compose exec -T php php artisan migrate --no-interaction
# Recreate Nginx so it loads the latest config and resolves the current PHP container.
docker compose up -d --wait --wait-timeout 120 --force-recreate nginx

printf '\nApplication started. Open the APP_URL configured in .env.\n'
