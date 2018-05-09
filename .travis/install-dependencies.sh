#!/usr/bin/env sh -ev

# Install chomedriver
wget https://chromedriver.storage.googleapis.com/2.38/chromedriver_linux64.zip
unzip -d $HOME chromedriver_linux64.zip
$HOME/chromedriver --url-base=/wd/hub &

# Install composer package
composer global require fxp/composer-asset-plugin