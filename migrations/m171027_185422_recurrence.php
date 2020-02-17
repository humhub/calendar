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
        $tableSchema = $this->db->getTableSchema('calendar_entry', true);

        // If the table does not exists, we want the default exception behavior
        if(!in_array('rrule', $tableSchema->columnNames, true)) {
            $this->addColumn('calendar_entry', 'rrule', $this->string()->null());
        }

        if(!in_array('parent_event_id', $tableSchema->columnNames, true)) {
            $this->addColumn('calendar_entry', 'parent_event_id', $this->integer()->null());
        }

        if(!in_array('recurrence_id', $tableSchema->columnNames, true)) {
            $this->addColumn('calendar_entry', 'recurrence_id', $this->string()->null());
        }

        if(!in_array('exdate', $tableSchema->columnNames, true)) {
            $this->addColumn('calendar_entry', 'exdate', $this->string()->null());
        }

        try {
            $this->addForeignKey('fk_calendar_entry_parent_event', 'calendar_entry', 'parent_event_id', 'calendar_entry', 'id', 'SET NULL');
        } catch(\Exception $e) {
            Yii::error($e);
        }
    }

    public function safeDown()
    {
        echo "m171027_185419_uid cannot be reverted.\n";

        return false;
    }
}
