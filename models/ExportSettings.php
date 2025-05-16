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
    public $includeUserInfo;

    /**
     * @var Module
     */
    public $module;

    public function init()
    {
        $this->module = Yii::$app->getModule('calendar');

        $this->jwtKey = $this->module->settings->get('jwtKey', $this->jwtKey);
        $this->jwtExpire = $this->module->settings->get('jwtExpiration', $this->jwtExpire);
        $this->includeUserInfo = $this->module->settings->get('includeUserInfo', $this->module->icsOrganizer);

        if (YII_ENV_TEST) {
            $this->jwtKey = 'test-key';
        }
    }

    public function rules()
    {
        return [
            [['jwtKey'], 'string', 'min' => 32, 'max' => 32],
            [['jwtExpire'], 'integer'],
            [['includeUserInfo'], 'boolean'],
            [['jwtExpire', 'includeUserInfo'], 'default', 'value' => 0],
        ];
    }

    public function attributeLabels()
    {
        return [
            'jwtKey' => Yii::t('CalendarModule.base', 'JWT Key'),
            'jwtExpire' => Yii::t('CalendarModule.base', 'JWT Token Expiration'),
            'includeUserInfo' => Yii::t('CalendarModule.base', 'Include Organizer and Participant Info in Exports'),
        ];
    }

    public function attributeHints()
    {
        return [
            'jwtKey' => Yii::t('CalendarModule.base', ' Used for secure iCal feed URL generation. Changing the key revokes all existing iCal URLs. If empty, a random key is generated automatically.'),
            'jwtExpire' => Yii::t('CalendarModule.base', 'in seconds. 0 for no JWT token expiration.'),
            'includeUserInfo' => Yii::t('CalendarModule.base', 'When enabled, calendar exports (ics, iCal, CalDAV) will include the organizer\'s and participant\'s names and email addresses. Disable to exclude this information for increased privacy.'),
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
        $this->module->settings->set('includeUserInfo', $this->includeUserInfo);

        return true;
    }
}
