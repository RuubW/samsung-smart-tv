#!/usr/bin/env bash
echo "php-cs-fixer started."

SCRIPT_PATH=$(dirname "$(realpath $0)")

cd "$SCRIPT_PATH/.."

PHP_CS_FIXER="$PWD/bin/php-cs-fixer"
PHP_CS_CONFIG="$PWD/.php_cs"

CHANGED_FILES=$(git diff --cached --name-only --diff-filter=ACM -- '*.php')

if [ -n "$CHANGED_FILES" ]; then
  $PHP_CS_FIXER fix $CHANGED_FILES;
  git add $CHANGED_FILES;
fi

echo "php-cs-fixer finished."