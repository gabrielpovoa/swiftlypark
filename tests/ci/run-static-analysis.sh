#!/usr/bin/env bash
set -euo pipefail

composer validate --strict --no-check-publish
composer audit --locked --no-interaction

while IFS= read -r -d '' php_file; do
    php -l "$php_file" >/dev/null
done < <(find app bin cli config core public tests -type f -name '*.php' -print0)

while IFS= read -r -d '' js_file; do
    node --check "$js_file"
done < <(find public/js -type f -name '*.js' -print0)

if git ls-files \
    | grep -Ev '^\.env\.example$' \
    | grep -Eq '(^|/)(\.env|id_rsa|id_ed25519)(\.|$)|\.(pem|p12|pfx|key)$'; then
    echo 'Arquivo potencialmente sensível está versionado.' >&2
    exit 1
fi

docker compose config --quiet
echo 'Static analysis passed'
