<?php

use yii\db\Migration;

/**
 * Class m220422_104254_double_location_length
 */
class m220422_104254_double_location_length extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('calendar_entry', 'location', 'varchar(128) DEFAULT NULL');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220422_104254_double_location_length cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {
    }
    public function down()
    {
        echo "m220422_104254_double_location_length cannot be reverted.\n";
        return false;
    }
    */
}
