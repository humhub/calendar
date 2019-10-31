<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

use yii\db\Migration;

class m171027_185420_reminder extends Migration
{
    public function safeUp()
    {
       $this->createTable('calendar_reminder', [
           'id' => $this->primaryKey(),
           'value' => $this->tinyInteger(4)->null(),
           'unit' => $this->tinyInteger(4)->null(),
           'object_model' => $this->string(200)->null(),
           'object_id' => $this->integer(11)->null(),
           'active' => $this->tinyInteger(4)->defaultValue(1),
           'contentcontainer_id' => $this->integer()->null()
       ]);

        $this->createTable('calendar_reminder_sent', [
            'id' => $this->primaryKey(),
            'reminder_id' => $this->integer()->notNull(),
            'object_model' => $this->string(200)->notNull(),
            'object_id' => $this->integer(11)->notNull(),
        ]);

       $this->addForeignKey('fk_calendar_reminder_container_id', 'calendar_reminder', 'contentcontainer_id', 'contentcontainer', 'id', 'CASCADE');
       $this->addForeignKey('fk_calendar_reminder_sent_id', 'calendar_reminder_sent', 'reminder_id', 'calendar_reminder', 'id', 'CASCADE');
    }

    public function safeDown()
    {
        echo "m171027_185419_uid cannot be reverted.\n";

        return false;
    }
}
