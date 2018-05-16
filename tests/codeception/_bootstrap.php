<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

/*
 * This is the initial test bootstrap, which will load the default test bootstrap from the humhub core
 */

$testRoot = dirname(__DIR__);

\Codeception\Configuration::append(['test_root' => $testRoot]);
echo 'Module root: ' . $testRoot . PHP_EOL;

$humhubPath = getenv('HUMHUB_PATH');
if ($humhubPath === false) {
    // If no environment path was set, we assume residing in default the modules directory
    $moduleConfig = require $testRoot . '/config/test.php';
    if (isset($moduleConfig['humhub_root'])) {
        $humhubPath = $moduleConfig['humhub_root'];
    } else {
        $humhubPath = dirname(__DIR__, 5);
    }
}

\Codeception\Configuration::append(['humhub_root' => $humhubPath]);
echo 'HumHub Root: ' . $humhubPath . PHP_EOL;

// Load test configuration (/config/test.php or /config/env/<environment>/test.php
$globalConfig = require $humhubPath . '/protected/humhub/tests/codeception/_loadConfig.php';

// Load default test bootstrap (initialize Yii...)
require $globalConfig['humhub_root'] . '/protected/humhub/tests/codeception/_bootstrap.php';
