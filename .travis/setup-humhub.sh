#!/usr/bin/env sh

# -e = exit when one command returns != 0, -v print each command before executing
set -ev

old=$(pwd)

mkdir ${HUMHUB_PATH}
cd ${HUMHUB_PATH}

git clone --branch master --depth 1 https://github.com/humhub/humhub.git .
composer install --prefer-dist --no-interaction

cd ${HUMHUB_PATH}/protected/humhub/tests

sed -i -e "s|'installed' => true,|'installed' => true,\n\t'moduleAutoloadPaths' => ['$(dirname $old)']|g" config/common.php
#cat config/common.php

mysql -e 'CREATE DATABASE humhub_test;'
php codeception/bin/yii migrate/up --includeModuleMigrations=1 --interactive=0
php codeception/bin/yii installer/auto
php codeception/bin/yii search/rebuild

php --server 127.0.0.1:8080 --docroot ${HUMHUB_PATH} &>/dev/null &
sleep 5
curl --fail --head http://127.0.0.1:8080/index-test.php

php ${HUMHUB_PATH}/protected/vendor/bin/codecept build