set -ex

if [ -n "$APP_ENV" ] && [ "$APP_ENV" == "prod" ]; then
	php bin/console secrets:decrypt-to-local --force --env=prod --no-debug --no-interaction
	composer dump-env prod
	rm -rf var/cache var/log
	runuser -u www-data -- php bin/console cache:clear --env=prod --no-debug --no-interaction
	runuser -u www-data -- php bin/console cache:warmup --env=prod --no-debug --no-interaction
else
	args="--no-progress --no-interaction --no-ansi"
	if [ -n "$APP_ENV" ] && [ "$APP_ENV" != "dev" ]; then
		args+=" --no-dev --classmap-authoritative"
	fi
	composer install ${args}
fi

if [[ ! -f "/initialized.flag" ]]; then
	APP_ENV_ORIG=$APP_ENV
	source .env
	[[ -f ".env.local" ]] && source .env.local
	APP_ENV=$APP_ENV_ORIG
	[[ -f ".env.$APP_ENV.local" ]] && source ".env.$APP_ENV.local"

	sed -i "s|__MINIO_ENDPOINT__|$MINIO_ENDPOINT|g" "/etc/nginx/sites/app.conf"

	touch /initialized.flag
fi

