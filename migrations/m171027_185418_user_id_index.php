<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use yii\db\Migration;

class m171027_185418_user_id_index extends Migration
{
    public function safeUp()
    {
        $this->execute('DELETE FROM calendar_entry_participant WHERE user_id NOT IN (SELECT id FROM user)');
        $this->addForeignKey('fk-calendar-participant-user-id', 'calendar_entry_participant', 'user_id', 'user', 'id', 'CASCADE');
    }

    public function safeDown()
    {
        echo "m171027_185418_user_id_index cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m171027_185418_user_id_index cannot be reverted.\n";

        return false;
    }
    */
}
