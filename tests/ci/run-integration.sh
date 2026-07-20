#!/usr/bin/env bash
set -euo pipefail

: "${DB_HOST:?DB_HOST obrigatório}"
: "${DB_DATABASE:?DB_DATABASE obrigatório}"
: "${DB_USERNAME:?DB_USERNAME obrigatório}"
: "${DB_PASSWORD:?DB_PASSWORD obrigatório}"

export MYSQL_PWD="$DB_PASSWORD"
mysql_args=(--host="$DB_HOST" --user="$DB_USERNAME" "$DB_DATABASE")

mysql "${mysql_args[@]}" < database/legacy/heidisql/parking.sql
sed '/^USE `parking`;/d' database/legacy/heidisql/migration-fin-001-payment-date.sql \
    | mysql "${mysql_args[@]}"

migration_dir="$(mktemp -d)"
trap 'rm -rf "$migration_dir"' EXIT
cp database/migrations/*.sql "$migration_dir/"
cp tests/Fixtures/ci-20260719-remove-memberships.sql \
    "$migration_dir/20260719_remove_unintended_swiftlypark_default_memberships.sql"

php tests/ci/migrate.php "$migration_dir"
php tests/Integration/FinancialLedgerIntegrationTest.php
php tests/Integration/ConcurrencyIntegrationTest.php
php tests/Integration/JobQueueIntegrationTest.php
