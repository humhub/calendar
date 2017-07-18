<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use yii\db\Migration;

class m170717_124743_safe_form_timezone extends Migration
{
    public function safeUp()
    {
        $this->addColumn('calendar_entry', 'time_zone', 'varchar(60) DEFAULT NULL');
    }

    public function safeDown()
    {
        echo "m170717_124743_safe_form_timezone cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m170717_124743_safe_form_timezone cannot be reverted.\n";

        return false;
    }
    */
}
