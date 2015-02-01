#!/bin/bash

set -e
set -x

# Install nginx.
sudo apt-get update
sudo apt-get install -y nginx realpath
sudo service nginx stop

USER=$(whoami)
DIR=$(realpath $(dirname "$0"))
DOCUMENT_ROOT=$(realpath "$DIR/../tests")
PHP_FPM_BIN="$HOME/.phpenv/versions/$TRAVIS_PHP_VERSION/sbin/php-fpm"
PHP_FPM_CONF="$DIR/php-fpm.conf"
PHP_FPM_LISTEN="$DIR/php-fpm.sock"

# Build the php-fpm.conf.
sudo sed -e "s|{USER}|$USER|g" -e "s|{PHP_FPM_LISTEN}|$PHP_FPM_LISTEN|g" < "$DIR/php-fpm.conf.tpl" > "$DIR/php-fpm.conf"

# Build the default site nginx conf.
sudo sed -e "s|{DOCUMENT_ROOT}|$DOCUMENT_ROOT|g" -e "s|{PHP_FPM_LISTEN}|$PHP_FPM_LISTEN|g" < "$DIR/default.conf.tpl" > "$DIR/default.conf"
sudo cp "$DIR/fastcgi.conf" /etc/nginx/fastcgi.conf
sudo cp "$DIR/default.conf" /etc/nginx/sites-enabled/default.conf

# Start the servers.
sudo $PHP_FPM_BIN --fpm-config "$DIR/php-fpm.conf"
sudo service nginx start