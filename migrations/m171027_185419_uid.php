<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use yii\db\Migration;

class m171027_185419_uid extends Migration
{
    public function safeUp()
    {
       $this->addColumn('calendar_entry', 'uid', $this->string(100));
    }

    public function safeDown()
    {
        echo "m171027_185419_uid cannot be reverted.\n";

        return false;
    }
}
