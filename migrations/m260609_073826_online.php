<?php

use humhub\components\Migration;

class m260609_073826_online extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->safeAddColumn('calendar_entry', 'online', $this->boolean()->defaultValue(false)->after('location'));
        $this->safeAlterColumn('calendar_entry', 'location', $this->string('1000'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m260609_073826_online cannot be reverted.\n";

        return false;
    }
}
