#!/bin/bash

set -e
set -x

sudo service nginx stop

USER=$(whoami)
DIR=$(realpath $(dirname "$0"))
TRAVIS_PHP_VERSION=$(phpenv version-name)
DOCUMENT_ROOT=$(realpath "$DIR/../tests")
PORT=9000
SERVER="127.0.0.1:$PORT"

if [ "$TRAVIS_PHP_VERSION" = 'hhvm' ] || [ "$TRAVIS_PHP_VERSION" = 'hhvm-nightly' ]
then
    HHVM_LOG_PATH="$DIR/hhvm.log"

    sudo hhvm \
        --mode=daemon \
        --user="$USER" \
        -vServer.Type=fastcgi \
        -vServer.Port="$PORT" \
        -vLog.File="$HHVM_LOG_PATH"
else
    PHP_FPM_BIN="$HOME/.phpenv/versions/$TRAVIS_PHP_VERSION/sbin/php-fpm"
    PHP_FPM_CONF="$DIR/php-fpm.conf"

    # Build the php-fpm.conf.
    sudo sed -e "s|{USER}|$USER|g" -e "s|{SERVER}|$SERVER|g" < "$DIR/php-fpm.conf.tpl" > "$DIR/php-fpm.conf"

    # Start php-fpm
    sudo "$PHP_FPM_BIN" --fpm-config "$DIR/php-fpm.conf"
fi

# Build the default site nginx conf.
sudo sed -e "s|{DOCUMENT_ROOT}|$DOCUMENT_ROOT|g" -e "s|{SERVER}|$SERVER|g" < "$DIR/default.conf.tpl" > "$DIR/default.conf"
sudo cp "$DIR/fastcgi.conf" /etc/nginx/fastcgi.conf
sudo cp "$DIR/default.conf" /etc/nginx/sites-enabled/default.conf

# Start the nginx.
sudo service nginx start
