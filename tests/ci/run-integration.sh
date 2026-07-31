#!/usr/bin/env bash
set -euo pipefail

: "${DB_HOST:?DB_HOST obrigatório}"
: "${DB_DATABASE:?DB_DATABASE obrigatório}"
: "${DB_USERNAME:?DB_USERNAME obrigatório}"
: "${DB_PASSWORD:?DB_PASSWORD obrigatório}"

export MYSQL_PWD="$DB_PASSWORD"
export PHINX_ENVIRONMENT=testing
mysql_args=(--host="$DB_HOST" --user="$DB_USERNAME" "$DB_DATABASE")

php cli/migrate.php migrate
mysql "${mysql_args[@]}" < tests/Fixtures/integration-database-seed.sql
php tests/Integration/FinancialLedgerIntegrationTest.php
php tests/Integration/ConcurrencyIntegrationTest.php
php tests/Integration/JobQueueIntegrationTest.php
