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
 * Date: 14.09.2017
 * Time: 14:51
 */

namespace humhub\modules\calendar\interfaces\event;


use humhub\modules\calendar\models\CalendarEntryType;
use humhub\modules\space\models\Space;
use Yii;
use yii\base\Model;
use humhub\modules\calendar\helpers\Url;
use humhub\components\SettingsManager;
use humhub\modules\content\components\ContentContainerActiveRecord;

/**
 * Instances of this class represent an event type of a specific container and can be used
 * to overwrite default values of the related CalendarType as color or enable/disable this type on
 * the container.
 *
 * If no container is given, this
 */
class CalendarEntryTypeSetting extends CalendarTypeSetting
{
    public $enabled = 1;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['color', 'required'],
            ['color', 'match', 'pattern' => static::COLOR_VALIDATON_PATTERN],
        ];
    }

    /**
     * Checks if the given item type is enabled for this
     */
    public function isEnabled()
    {
        return true;
    }

    public function updateEnabled($isEnabled)
    {
        return true;
    }

    public function canBeDisabled()
    {
        return false;
    }

    /**
     * @return string The options default color or fallback color if not color was defined.
     */
    public function getDefaultColor()
    {
        if($this->contentContainer instanceof  Space) {
            return $this->contentContainer->color;
        }

        return parent::getDefaultColor();
    }
}