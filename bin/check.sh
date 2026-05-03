#!/usr/bin/env bash
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

echo "==> Syntax check (php -l)"
find "${ROOT_DIR}/src" -name "*.php" -print0 | xargs -0 -n1 php -l

echo ""
echo "==> Unit tests"
"${ROOT_DIR}/vendor/bin/phpunit" --testsuite Unit

echo ""
echo "==> PHPStan"
"${ROOT_DIR}/vendor/bin/phpstan" analyse

echo ""
echo "==> PSR-12 code style"
"${ROOT_DIR}/vendor/bin/phpcs"

echo ""
echo "All checks passed."
