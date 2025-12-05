#!/bin/bash

# Path to your google calendar directory.
MONOREPO_PATH="docroot/google-calendar/google-api-php-client-main"

echo "Running composer install in $MONOREPO_PATH..."
cd "$MONOREPO_PATH" && composer install --no-interaction

# Check if script is running during CI build, if yes add vendor to git.
if [ "$CI" = "true" ]; then
  echo "CI is true; force-adding vendor in google-calendar to git."
  git add -f "vendor"
fi

exit 0
