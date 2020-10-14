<?php

use yii\db\Migration;

/**
 * Class m201013_172104_add_calendar_entry_location
 */
class m201013_172104_add_calendar_entry_location extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('calendar_entry', 'location', 'varchar(64) DEFAULT NULL');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m201013_172104_add_calendar_entry_location cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201013_172104_add_location_to_calendar_entry cannot be reverted.\n";

        return false;
    }
    */
}
