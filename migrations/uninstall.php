<?php

class uninstall extends ZDbMigration {

    public function up() {

        $this->dropTable('calendar_entry');
        $this->dropTable('calendar_entry_participant');
    }

    public function down() {
        echo "uninstall does not support migration down.\n";
        return false;
    }

}