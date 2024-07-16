<?php

use humhub\components\Migration;

/**
 * Class m240716_075328_fix_exdate
 */
class m240716_075328_fix_exdate extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('calendar_entry', 'exdate', $this->text()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240716_075328_fix_exdate cannot be reverted.\n";

        return false;
    }
}
