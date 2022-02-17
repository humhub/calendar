<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\components\Migration;

/**
 * Class m220217_085029_entry_options
 */
class m220217_085029_entry_options extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $type = $this->tinyInteger(1)->defaultValue(0)->notNull()->unsigned();
        $this->safeAddColumn('calendar_entry', 'recurring', $type->after('all_day'));
        $this->safeAddColumn('calendar_entry', 'reminder', $type->after('recurring'));
        // Enable new options for all existing calendar entries:
        $this->execute('UPDATE calendar_entry SET recurring = 1, reminder = 1');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->safeDropColumn('calendar_entry', 'recurring');
        $this->safeDropColumn('calendar_entry', 'reminder');
    }
}
