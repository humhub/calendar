<?php

use humhub\components\Migration;

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */


class m171027_185421_drop_legacy_columns extends Migration
{
    public function safeUp()
    {
        $this->dropColumn('calendar_entry', 'recur');
        $this->dropColumn('calendar_entry', 'recur_type');
        $this->dropColumn('calendar_entry', 'recur_interval');
        $this->dropColumn('calendar_entry', 'recur_end');
    }

    public function safeDown()
    {
        echo "m171027_185419_uid cannot be reverted.\n";

        return false;
    }
}
