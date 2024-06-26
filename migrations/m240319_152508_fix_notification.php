<?php

use humhub\components\Migration;
use humhub\modules\notification\models\Notification;

/**
 * Class m240319_152508_fix_notification
 */
class m240319_152508_fix_notification extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        Notification::updateAll(
            ['class' => 'humhub\\modules\\calendar\\notifications\\ParticipantAdded'],
            ['IN', 'class', [
                'humhub\\modules\\calendar\\notifications\\Invited',
                'humhub\\modules\\calendar\\notifications\\ForceAdd',
            ]],
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240319_152508_fix_notification cannot be reverted.\n";

        return false;
    }
}
