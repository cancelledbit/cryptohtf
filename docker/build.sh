#!/usr/bin/env bash

set -e

function main () {
	if [ -z "$ONLY_FRONT" ]; then
		backend
	fi
	if [ -z "$ONLY_BACK" ]; then
		frontend
	fi
}

function frontend () {
  echo "front"
#	local args="--non-interactive --non-interactive"
#	yarn install ${args}
#	yarn build
}

function backend () {
	local args="--no-progress --no-interaction --no-ansi"
	if [ -n "$APP_ENV" ] && [ "$APP_ENV" != "dev" ]; then
		args+=" --no-dev --classmap-authoritative"
	fi
	composer install ${args}
	chmod -R a+rwX var
	runuser -u www-data -- php bin/console assets:install --no-debug --no-interaction
	php -r "file_put_contents('vendor/symfony/intl/Resources/data/timezones/ru.json', json_encode(include 'vendor/symfony/intl/Resources/data/timezones/ru.php'));"
}

main
