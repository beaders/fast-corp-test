#!/usr/bin/env bash
set -euo pipefail

source "$(dirname -- "${BASH_SOURCE[0]}")/scripts/common.sh"

if [[ ! -f .env || ! -f vendor/autoload.php ]]; then
    printf 'Run ./start.sh once to prepare .env and PHP dependencies.\n' >&2
    exit 1
fi

exec docker compose run --rm --no-deps -T php composer test -- "$@"
