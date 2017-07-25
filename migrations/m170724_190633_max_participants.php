<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use yii\db\Migration;

class m170724_190633_max_participants extends Migration
{
    public function safeUp()
    {
        $this->addColumn('calendar_entry', 'max_participants', $this->integer());
    }

    public function safeDown()
    {
        echo "m170724_190633_max_participants cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m170724_190633_max_participants cannot be reverted.\n";

        return false;
    }
    */
}
