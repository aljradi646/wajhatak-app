#!/usr/bin/env bash
set -euo pipefail
SCRIPT_DIR="$(cd -- "$(dirname "$0")" && pwd)"
exec docker compose -f "$SCRIPT_DIR/../llm/docker-compose.yml" up -d --build
