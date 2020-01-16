<?php

use humhub\components\Migration;

class m140729_223509_initial extends Migration
{

    public function up()
    {
        $this->createTable('calendar_entry', [
            'id' => 'pk',
            'title' => 'varchar(255) NOT NULL',
            'description' => 'TEXT NULL',
            'start_time' => 'datetime NOT NULL',
            'end_time' => 'datetime NOT NULL',
            'all_day' => 'tinyint(4) NOT NULL',
            'participation_mode' => 'tinyint(4) NOT NULL',
            'recur' => 'tinyint(4) NULL',
            'recur_type' => 'tinyint(4) NULL',
            'recur_interval' => 'tinyint(4) NULL',
            'recur_end' => 'datetime NULL',
        ], '');

        $this->createTable('calendar_entry_participant', [
            'id' => 'pk',
            'calendar_entry_id' => 'int(11) NOT NULL',
            'user_id' => 'int(11) NOT NULL',
            'participation_state' => 'tinyint(4) NULL',
        ], '');

        $this->createIndex('unique_entryuser', 'calendar_entry_participant', 'calendar_entry_id,user_id', true);
    }

    public function down()
    {
        echo "m140729_223509_initial does not support migration down.\n";
        return false;
    }

    /*
      // Use safeUp/safeDown to do migration with transaction
      public function safeUp()
      {
      }

      public function safeDown()
      {
      }
     */
}
