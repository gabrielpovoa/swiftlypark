#!/usr/bin/env bash
set -euo pipefail

for test_file in tests/*Test.php; do
    php "$test_file"
done

php tests/SecurityTestSuite.php
