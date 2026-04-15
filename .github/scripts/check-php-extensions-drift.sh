#!/usr/bin/env bash
#
# Проверяет что список PHP-расширений в docker/php/Dockerfile (base stage)
# совпадает с extensions в .github/workflows/ci.yml.
#
# Зачем: CI гоняет тесты на native PHP через shivammathur/setup-php с ручным
# списком расширений. Dockerfile (prod/test образ) ставит расширения через
# docker-php-ext-install + pecl install. Эти два списка — два источника правды,
# между ними легко получить drift: добавил soap в Dockerfile, забыл в CI →
# тесты зелёные, prod валится.
#
# xdebug исключается — он только в dev-stage (локальный debug), в CI не нужен.
#
# Usage:
#   bash .github/scripts/check-php-extensions-drift.sh
#
# Exit 0 если списки совпадают, 1 если есть расхождение.

set -euo pipefail

DOCKERFILE="${DOCKERFILE:-docker/php/Dockerfile}"
CI_YAML="${CI_YAML:-.github/workflows/ci.yml}"

# Расширения, которые в CI не нужны даже если они в Dockerfile (dev-only tooling).
EXCLUDE_FROM_CI="xdebug"

extract_dockerfile_exts() {
    # docker-php-ext-install блок в base stage: ловим имена до "apk del".
    awk '/docker-php-ext-install/,/apk del/' "$DOCKERFILE" \
        | grep -oE '^\s+[a-z_][a-z0-9_]*(\s*\\)?$' \
        | sed -E 's/\s*\\?\s*$//' \
        | tr -d ' '
    # pecl install в base stage (до начала stage "dev").
    awk '/^FROM.*AS base/,/^FROM.*AS dev/' "$DOCKERFILE" \
        | grep -oE 'pecl install [a-z_-]+' \
        | awk '{print $3}'
}

extract_ci_exts() {
    # Первая "extensions:" строка в ci.yml (backend-test job).
    grep -m1 'extensions:' "$CI_YAML" \
        | sed 's/.*extensions: *//' \
        | tr ',' '\n' \
        | tr -d ' '
}

dockerfile_sorted=$(extract_dockerfile_exts | grep -v -E "^(${EXCLUDE_FROM_CI})$" | sort -u | grep -v '^$')
ci_sorted=$(extract_ci_exts | sort -u | grep -v '^$')

echo "Dockerfile base stage extensions (minus ${EXCLUDE_FROM_CI}):"
echo "$dockerfile_sorted" | sed 's/^/  /'
echo ""
echo "CI extensions (.github/workflows/ci.yml):"
echo "$ci_sorted" | sed 's/^/  /'
echo ""

if [[ "$dockerfile_sorted" != "$ci_sorted" ]]; then
    echo "❌ PHP extensions drift detected:"
    diff <(echo "$dockerfile_sorted") <(echo "$ci_sorted") || true
    echo ""
    echo "Fix: синхронизируй списки в docker/php/Dockerfile и .github/workflows/ci.yml."
    exit 1
fi

echo "✅ PHP extensions in sync"
