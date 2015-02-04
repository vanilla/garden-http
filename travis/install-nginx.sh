#!/bin/bash

set -e
set -x

TRAVIS_PHP_VERSION=$(phpenv version-name)

#if [ "$TRAVIS_PHP_VERSION" = 'hhvm-nightly' ]
#then
#	sudo add-apt-repository -y ppa:mapnik/boost
#fi

# Install nginx.
sudo apt-get update
sudo apt-get install -y nginx realpath
sudo apt-get install libgmp10
