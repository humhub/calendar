<?php

use yii\db\Migration;

class uninstall extends Migration
{

    public function up()
    {
        $this->dropTable('calendar_entry');
        $this->dropTable('calendar_entry_participant');
        $this->dropTable('calendar_reminder');
        $this->dropTable('calendar_reminder_sent');
    }

    public function down()
    {
        echo "uninstall does not support migration down.\n";
        return false;
    }

}
