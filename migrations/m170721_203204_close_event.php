<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use yii\db\Migration;
use yii\db\Schema;

class m170721_203204_close_event extends Migration
{
    public function safeUp()
    {
        $this->addColumn('calendar_entry', 'closed', Schema::TYPE_BOOLEAN. ' DEFAULT 0');
    }

    public function safeDown()
    {
        echo "m170721_203204_close_event cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m170721_203204_close_event cannot be reverted.\n";

        return false;
    }
    */
}
