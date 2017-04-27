<?php

use yii\db\Migration;

class m160308_111526_add_colors extends Migration
{
    public function up()
    {
        $this->addColumn('calendar_entry', 'color', $this->string()); 
    }

    public function down()
    {
        $this->dropColumn('calendar_entry', 'color'); 
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
