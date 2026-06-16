<?php

use yii\db\Migration;

class m250603_194724_export_jwt_key extends Migration
{
    public function safeUp()
    {
        $jwtKey = $this->db->createCommand(
            'SELECT value FROM setting WHERE module_id = :m AND name = :n',
            [':m' => 'calendar', ':n' => 'jwtKey'],
        )->queryScalar();

        if (empty($jwtKey)) {
            $jwtKey = Yii::$app->security->generateRandomString();

            $this->upsert('setting', [
                'module_id' => 'calendar',
                'name' => 'jwtKey',
                'value' => $jwtKey,
            ], [
                'value' => $jwtKey,
            ]);
        }
    }

    public function safeDown()
    {
        echo "m250603_194724_export_jwt_key cannot be reverted.\n";

        return false;
    }
}
