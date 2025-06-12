<?php

use humhub\modules\calendar\models\ExportSettings;
use yii\db\Migration;

class m250603_194724_export_jwt_key extends Migration
{
    public function safeUp()
    {
        ExportSettings::instance()->save();
    }

    public function safeDown()
    {
        echo "m250603_194724_export_jwt_key cannot be reverted.\n";

        return false;
    }
}
