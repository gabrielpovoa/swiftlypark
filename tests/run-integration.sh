#!/usr/bin/env bash
set -euo pipefail

php cli/migrate.php migrate
php tests/Integration/FinancialLedgerIntegrationTest.php
php tests/Integration/ConcurrencyIntegrationTest.php
