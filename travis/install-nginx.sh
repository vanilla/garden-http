#!/bin/bash

set -e
set -x

TRAVIS_PHP_VERSION=$(phpenv version-name)

# Install nginx.
sudo apt-get update
sudo apt-get install -y nginx realpath
sudo apt-get install libgmp10
