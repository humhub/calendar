<?php

use yii\db\Schema;
use yii\db\Migration;

class m150706_193118_renamefield extends Migration
{
    public function up()
    {
        $this->renameColumn('calendar_entry', 'start_time', 'start_datetime');
        $this->renameColumn('calendar_entry', 'end_time', 'end_datetime');
    }

    public function down()
    {
        echo "m150706_193118_renamefield cannot be reverted.\n";

        return false;
    }
    
    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }
    
    public function safeDown()
    {
    }
    */
}
