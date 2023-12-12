ARG BASE_VERSION=latest
FROM cfh-base:${BASE_VERSION}

# APP
#ADD . /cfh/cfh
WORKDIR /cfh/cfh
#NGINX ADDITIONAL
ADD docker/configs/nginx /etc/nginx

ARG APP_ENV=dev
ENV APP_ENV=${APP_ENV}
ARG APP_DEBUG=1
ENV APP_DEBUG=${APP_DEBUG}
RUN apt update && apt install -y gocryptfs php-pgsql

RUN docker/build.sh

CMD ["/cfh/cfh/docker/web-command.sh"]
