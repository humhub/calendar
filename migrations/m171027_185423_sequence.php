<?php

use humhub\components\Migration;

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */


class m171027_185423_sequence extends Migration
{
    public function safeUp()
    {
        $this->addColumn('calendar_entry', 'sequence', $this->integer()->defaultValue(0));
    }

    public function safeDown()
    {
        echo "m171027_185423_sequence.\n";

        return false;
    }
}
