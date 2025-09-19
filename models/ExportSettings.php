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
    public $includeParticipantInfo;
    public $includeParticipantEmail;

    /**
     * @var Module
     */
    public $module;

    public function init()
    {
        $this->module = Yii::$app->getModule('calendar');

        $this->jwtKey = $this->module->settings->get('jwtKey', $this->jwtKey);
        $this->jwtExpire = $this->module->settings->get('jwtExpiration', $this->jwtExpire);
        $this->includeParticipantInfo = $this->module->settings->get('includeParticipantInfo', $this->module->icsOrganizer);
        $this->includeParticipantEmail = $this->module->settings->get('includeParticipantEmail', false);

        if (YII_ENV_TEST) {
            $this->jwtKey = 'test-key';
        }
    }

    public function rules()
    {
        return [
            [['jwtKey'], 'string', 'min' => 32, 'max' => 32],
            [['jwtExpire'], 'integer'],
            [['includeParticipantInfo', 'includeParticipantEmail'], 'boolean'],
            [['jwtExpire', 'includeParticipantInfo', 'includeParticipantEmail'], 'default', 'value' => 0],
        ];
    }

    public function attributeLabels()
    {
        return [
            'jwtKey' => Yii::t('CalendarModule.base', 'JWT Key'),
            'jwtExpire' => Yii::t('CalendarModule.base', 'JWT Token Expiration'),
            'includeParticipantInfo' => Yii::t('CalendarModule.base', 'Include participant information in exports'),
            'includeParticipantEmail' => Yii::t('CalendarModule.base', 'Also include participant email addresses'),
        ];
    }

    public function attributeHints()
    {
        return [
            'jwtKey' => Yii::t('CalendarModule.base', 'Used for secure iCal feed URL generation and CalDAV authentication. Changing this key will revoke all existing iCal URLs and CalDAV logins. If empty, a random key is generated automatically.'),
            'jwtExpire' => Yii::t('CalendarModule.base', 'in seconds. 0 for no JWT token expiration.'),
            'includeParticipantInfo' => Yii::t('CalendarModule.base', 'When enabled, attendee names are included in ICS and CalDAV exports. The organizer is always included.'),
            'includeParticipantEmail' => Yii::t('CalendarModule.base', 'In addition to names, attendees’ email addresses will be included in ICS and CalDAV exports. Enable only if allowed by your privacy policy or data protection rules.'),
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

        if (!$this->includeParticipantInfo) {
            $this->includeParticipantEmail = false;
        }

        $this->module->settings->set('jwtKey', $this->jwtKey);
        $this->module->settings->set('jwtExpire', (int) $this->jwtExpire);
        $this->module->settings->set('includeParticipantInfo', $this->includeParticipantInfo);
        $this->module->settings->set('includeParticipantEmail', $this->includeParticipantEmail);

        return true;
    }
}
