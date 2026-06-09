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
        $this->safeAddColumn('calendar_entry', 'participation_url', $this->string()->after('participation_mode'));
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
