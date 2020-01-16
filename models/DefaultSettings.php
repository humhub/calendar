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

use humhub\modules\calendar\models\reminder\forms\ReminderSettings;
use humhub\modules\calendar\models\participation\ParticipationSettings;
use humhub\modules\content\components\ContentContainerActiveRecord;
use Yii;
use yii\base\Model;
use humhub\modules\calendar\helpers\Url;

class DefaultSettings extends Model
{
    /**
     * @var ContentContainerActiveRecord
     */
    public $contentContainer;

    /**
     * @var ReminderSettings
     */
    public $reminderSettings;

    public $participationSettings;

    public function init()
    {
        parent::init();
        $this->initSettings();
    }

    private function initSettings()
    {
        $this->reminderSettings = new ReminderSettings(['container' => $this->contentContainer]);
        $this->participationSettings = new ParticipationSettings(['contentContainer' => $this->contentContainer]);
    }



    public function load($data, $formName = null)
    {
         return $this->participationSettings->load($data) | $this->reminderSettings->load($data);
    }

    public function save()
    {
        $this->reminderSettings->save();
        $this->participationSettings->save();
        return true;
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
