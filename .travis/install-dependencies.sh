#!/usr/bin/env sh

# -e = exit when one command returns != 0, -v print each command before executing
set -ev

# Install chomedriver
curl -s -L -o chromedriver_linux64.zip https://chromedriver.storage.googleapis.com/2.38/chromedriver_linux64.zip \
    && unzip -o -d $HOME chromedriver_linux64.zip

# Start chomedriver
$HOME/chromedriver --url-base=/wd/hub &

# Install composer package
composer global require fxp/composer-asset-plugin