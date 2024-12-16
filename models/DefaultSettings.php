<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 17.07.2017
 * Time: 21:15
 */

namespace humhub\modules\calendar\models;

use humhub\modules\calendar\helpers\Url;
use humhub\modules\calendar\models\forms\BasicSettings;
use humhub\modules\calendar\models\participation\FullCalendarSettings;
use humhub\modules\calendar\models\participation\ParticipationSettings;
use humhub\modules\calendar\models\reminder\forms\ReminderSettings;
use humhub\modules\content\components\ContentContainerActiveRecord;
use yii\base\Model;

class DefaultSettings extends Model
{
    /**
     * @var ContentContainerActiveRecord
     */
    public $contentContainer;

    /**
     * @var BasicSettings
     */
    public $basicSettings;

    /**
     * @var ReminderSettings
     */
    public $reminderSettings;

    /**
     * @var ParticipationSettings
     */
    public $participationSettings;

    /**
     * @var FullCalendarSettings
     */
    public $fullCalendarSettings;


    public function init()
    {
        parent::init();
        $this->initSettings();
    }

    private function initSettings()
    {
        $this->basicSettings = new BasicSettings(['contentContainer' => $this->contentContainer]);
        $this->reminderSettings = new ReminderSettings(['container' => $this->contentContainer]);
        $this->participationSettings = new ParticipationSettings(['contentContainer' => $this->contentContainer]);
        $this->fullCalendarSettings = new FullCalendarSettings(['contentContainer' => $this->contentContainer]);
    }


    public function load($data, $formName = null)
    {
        return $this->basicSettings->load($data) |
            $this->participationSettings->load($data) |
            $this->reminderSettings->load($data) |
            $this->fullCalendarSettings->load($data);
    }

    public function save(): bool
    {
        return $this->basicSettings->save() &&
            $this->participationSettings->save() &&
            $this->reminderSettings->save() &&
            $this->fullCalendarSettings->save();
    }

    public function isGlobal()
    {
        return $this->contentContainer === null;
    }

    public function getSubmitUrl()
    {
        return Url::toConfig($this->contentContainer);
    }
}
