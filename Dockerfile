#
# PHP Composer dependencies
#
FROM composer:2.1.3 as vendor

ARG PACKAGIST_AUTH_TOKEN
COPY database database/
COPY composer.json composer.json
COPY composer.lock composer.lock

RUN composer config --global --auth http-basic.repo.packagist.com token $PACKAGIST_AUTH_TOKEN
RUN composer install \
    --no-dev \
    --quiet \
    --ignore-platform-reqs \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist

##
# Application
##
FROM serviceright/serviceright-docker-laravel-php80:latest

RUN apk add php-zip

COPY . /usr/share/nginx/html
COPY --from=vendor /app/vendor/ /usr/share/nginx/html/vendor/

COPY docker/config/supervisor /etc/supervisor
COPY docker/bootstrap-application /etc/bootstrap-application

RUN chmod +x /etc/bootstrap-application
COPY docker/config/php.ini /usr/local/etc/php/php.ini

# Create the log files
RUN chmod -R 777 /usr/share/nginx/html/storage

## THE LIFE SAVER
ADD https://github.com/ufoscout/docker-compose-wait/releases/download/2.2.1/wait /wait
RUN chmod +x /wait

CMD ["/etc/bootstrap-application"]
