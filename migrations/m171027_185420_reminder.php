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
            'content_id' => $this->integer()->null(),
            'active' => $this->tinyInteger(4)->defaultValue(1),
            'disabled' => $this->tinyInteger(4)->defaultValue(0),
            'contentcontainer_id' => $this->integer()->null()
        ]);

        $this->createTable('calendar_reminder_sent', [
            'id' => $this->primaryKey(),
            'reminder_id' => $this->integer()->notNull(),
            'content_id' => $this->integer()->null(),
        ]);

        $this->addForeignKey('fk_calendar_reminder_container_id', 'calendar_reminder', 'contentcontainer_id', 'contentcontainer', 'id', 'CASCADE');
        $this->addForeignKey('fk_calendar_reminder_content_id', 'calendar_reminder', 'content_id', 'content', 'id', 'CASCADE');
        $this->addForeignKey('fk_calendar_reminder_sent_id', 'calendar_reminder_sent', 'reminder_id', 'calendar_reminder', 'id', 'CASCADE');
        $this->addForeignKey('fk_calendar_reminder_sent_content_id', 'calendar_reminder_sent', 'content_id', 'content', 'id', 'CASCADE');
    }

    public function safeDown()
    {
        echo "m171027_185419_uid cannot be reverted.\n";

        return false;
    }
}
