<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use yii\db\Migration;

class m170717_123220_allow_maybe_decline_flag extends Migration
{
    public function safeUp()
    {
        $this->addColumn('calendar_entry', 'allow_decline', 'tinyint(4) DEFAULT 1');
        $this->addColumn('calendar_entry', 'allow_maybe', 'tinyint(4) DEFAULT 1');
    }

    public function safeDown()
    {
        echo "m170717_123220_allow_maybe_decline_flag cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m170717_123220_allow_maybe_decline_flag cannot be reverted.\n";

        return false;
    }
    */
}
