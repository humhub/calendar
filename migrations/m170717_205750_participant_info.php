<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use yii\db\Migration;

class m170717_205750_participant_info extends Migration
{
    public function safeUp()
    {
        $this->addColumn('calendar_entry', 'participant_info', 'TEXT NULL');
    }

    public function safeDown()
    {
        echo "m170717_205750_attandee_info cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m170717_205750_attandee_info cannot be reverted.\n";

        return false;
    }
    */
}
