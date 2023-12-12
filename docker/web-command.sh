#!/usr/bin/env bash

set -ex

source docker/common.sh

/usr/sbin/php-fpm8.2 --daemonize --fpm-config /etc/php/8.2/fpm/php-fpm.conf

/usr/sbin/nginx

sleep infinity
