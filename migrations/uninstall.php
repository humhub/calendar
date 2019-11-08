<?php


use humhub\components\Migration;

class uninstall extends Migration
{

    public function up()
    {
        $this->safeDropTable('calendar_entry');
        $this->safeDropTable('calendar_entry_participant');
        $this->safeDropTable('calendar_reminder');
        $this->safeDropTable('calendar_reminder_sent');
    }

    public function down()
    {
        echo "uninstall does not support migration down.\n";
        return false;
    }

}
