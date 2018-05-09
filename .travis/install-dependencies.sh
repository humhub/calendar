#!/usr/bin/env sh

# -e = exit when one command returns != 0, -v print each command before executing
set -ev

# Install chomedriver
wget https://chromedriver.storage.googleapis.com/2.38/chromedriver_linux64.zip
unzip -d $HOME chromedriver_linux64.zip
$HOME/chromedriver --url-base=/wd/hub &

# Install composer package
composer global require fxp/composer-asset-plugin