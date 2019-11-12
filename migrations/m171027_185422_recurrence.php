<?php

use humhub\components\Migration;

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */


class m171027_185422_recurrence extends Migration
{
    public function safeUp()
    {
        $this->addColumn('calendar_entry', 'rrule', $this->string()->null());
        $this->addColumn('calendar_entry', 'parent_event_id', $this->integer()->null());
        $this->addColumn('calendar_entry', 'recurrence_id', $this->string()->null());
        $this->addColumn('calendar_entry', 'exdate', $this->string()->null());

        $this->addForeignKey('fk_calendar_entry_parent_event', 'calendar_entry', 'parent_event_id', 'calendar_entry', 'id', 'CASCADE');
        $this->createIndex('idx_unique_calendar_entry_recurrence', 'calendar_entry', ['parent_event_id', 'recurrence_id']);
    }

    public function safeDown()
    {
        echo "m171027_185419_uid cannot be reverted.\n";

        return false;
    }
}
