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
 * Date: 23.07.2017
 * Time: 16:55
 */

namespace humhub\modules\calendar\models;


use humhub\modules\calendar\interfaces\event\CalendarTypeIF;
use humhub\modules\content\models\ContentTag;
use Yii;

class CalendarEntryType extends ContentTag implements CalendarTypeIF
{
    public $moduleId = 'calendar';

    const DEFAULT_COLOR = '#59D6E4';
    const KEY = 'calendar_event';
    const ICON = 'fa-calendar';

    public function init()
    {
        // Default color
        $this->color = static::DEFAULT_COLOR;
        parent::init();
    }

    public function getDefaultColor()
    {
        return static::DEFAULT_COLOR;
    }

    public function getTitle()
    {

        return $this->name ? $this->name : Yii::t('CalendarModule.base', 'Event');
    }

    public function getDescription()
    {
        return null;
    }

    public function getIcon()
    {
        return static::ICON;
    }

    public function getKey()
    {
        return static::KEY;
    }
}