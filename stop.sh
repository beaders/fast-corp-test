#!/usr/bin/env bash
set -euo pipefail

source "$(dirname -- "${BASH_SOURCE[0]}")/scripts/common.sh"

if [[ -f .env ]]; then
    docker compose down
else
    docker compose --env-file .env.example down
fi

printf '\nApplication stopped. MySQL and Redis data have been preserved.\n'
