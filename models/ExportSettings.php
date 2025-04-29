<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\models;

use humhub\modules\calendar\Module;
use Yii;
use yii\base\Model;

class ExportSettings extends Model
{
    public $jwtKey;
    public $jwtExpire = 0;

    /**
     * @var Module
     */
    public $module;

    public function init()
    {
        $this->module = Yii::$app->getModule('calendar');

        $this->jwtKey = $this->module->settings->get('jwtKey', $this->jwtKey);
        $this->jwtExpire = $this->module->settings->get('jwtExpiration', $this->jwtExpire);
    }

    public function rules()
    {
        return [
            [['jwtKey'], 'string', 'min' => 32, 'max' => 32],
            [['jwtExpire'], 'integer'],
            [['jwtExpire'], 'default', 'value' => 0],
        ];
    }

    public function attributeLabels()
    {
        return [
            'jwtKey' => Yii::t('CalendarModule.base', 'JWT Key'),
            'jwtExpire' => Yii::t('CalendarModule.base', 'JWT Token Expiration'),
        ];
    }

    public function attributeHints()
    {
        return [
            'jwtKey' => Yii::t('CalendarModule.base', 'If empty, a random key is generated automatically.'),
            'jwtExpire' => Yii::t('CalendarModule.base', 'in seconds. 0 for no JWT token expiration.'),
        ];
    }

    /**
     * @return bool
     */
    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        if (empty($this->jwtKey)) {
            $this->jwtKey = Yii::$app->security->generateRandomString();
        }

        $this->module->settings->set('jwtKey', $this->jwtKey);
        $this->module->settings->set('jwtExpire', (int) $this->jwtExpire);

        return true;
    }
}
