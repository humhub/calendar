<?php

use yii\db\Migration;

class m250502_082039_calendar_entry_external_participant extends Migration
{
    public function safeUp()
    {
        $this->alterColumn('calendar_entry_participant', 'user_id', $this->integer()->null());
        $this->addColumn('calendar_entry_participant', 'external_user_email', $this->string()->null()->after('user_id'));
    }

    public function safeDown()
    {
        $this->alterColumn('calendar_entry_participant', 'user_id', $this->integer()->notNull());
        $this->dropColumn('calendar_entry_participant', 'external_user_email');
    }
}
