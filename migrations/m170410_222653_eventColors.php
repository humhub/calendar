<?php

use yii\db\Migration;

class m170410_222653_eventColors extends Migration
{
    public function up()
    {
        $this->addColumn('calendar_entry', 'color', 'varchar(7)');
    }

    public function down()
    {
        echo "m170410_222653_eventColors cannot be reverted.\n";

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
