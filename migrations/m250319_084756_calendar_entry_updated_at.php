<?php

use humhub\components\Migration;

class m250319_084756_calendar_entry_updated_at extends Migration
{
    public function safeUp()
    {
        $this->addColumn('calendar_entry', 'updated_at', $this->dateTime()->defaultExpression('now()')->notNull());
    }

    public function safeDown()
    {
        $this->dropColumn('calendar_entry', 'updated_at');
    }
}
