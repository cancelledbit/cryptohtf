#!/usr/bin/env bash

set -ex

source docker/common.sh

touch currentcron
echo "* * * * * php /cfh/cfh/bin/console app:close-mounts" >> currentcron
crontab currentcron
touch /tmp/cron.log
cron start

/usr/sbin/php-fpm8.2 --daemonize --fpm-config /etc/php/8.2/fpm/php-fpm.conf

/usr/sbin/nginx

sleep infinity
