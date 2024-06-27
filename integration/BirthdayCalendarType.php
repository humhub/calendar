<?php

namespace humhub\modules\calendar\integration;

use humhub\modules\calendar\interfaces\event\CalendarTypeIF;
use Yii;

class BirthdayCalendarType implements CalendarTypeIF
{
    public const ITEM_TYPE_KEY = 'birthday';

    public const DEFAULT_COLOR = '#59D6E4';

    /**
     * Returns a unique key of this type of event.
     * The event type key is besides others used in the autogeneration of uids and should therefore not use any special
     * characters or spaces.
     *
     * @return string an unique event type key.
     */
    public function getKey()
    {
        return static::ITEM_TYPE_KEY;
    }

    /**
     * @return string a translated title for this kind of event
     */
    public function getTitle()
    {
        return Yii::t('CalendarModule.base', 'Birthday');
    }

    /**
     * @return string a translated description of this type of event
     */
    public function getDescription()
    {
        return Yii::t('CalendarModule.base', 'User birthdays');
    }

    /**
     * @return string an optional default color (e.g. #ffffff) used for this kind of events which can be overwritten on global or space level.
     */
    public function getDefaultColor()
    {
        return static::DEFAULT_COLOR;
    }

    /**
     * @return string an optional icon string
     */
    public function getIcon()
    {
        return 'fa-birthday-cake';
    }
}
