#!/usr/bin/env bash

PROJECT_ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
cd -- "$PROJECT_ROOT"

if ! command -v docker >/dev/null 2>&1; then
    printf 'Docker is required. Install Docker Desktop or Docker Engine.\n' >&2
    exit 1
fi

if ! docker compose version >/dev/null 2>&1; then
    printf 'Docker Compose v2 or newer is required.\n' >&2
    exit 1
fi

if ! docker info >/dev/null 2>&1; then
    printf 'Docker is not running. Start Docker and try again.\n' >&2
    exit 1
fi
