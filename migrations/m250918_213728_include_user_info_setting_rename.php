<?php

use humhub\models\Setting;
use yii\db\Migration;

class m250918_213728_include_user_info_setting_rename extends Migration
{
    public function safeUp()
    {
        Setting::updateAll(['name' => 'includeParticipantInfo'], ['name' => 'includeUserInfo', 'module_id' => 'calendar']);
    }

    public function safeDown()
    {
        echo "m250918_213728_include_user_info_setting_rename cannot be reverted.\n";

        return false;
    }
}
