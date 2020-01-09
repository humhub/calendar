<?php


namespace humhub\modules\calendar\tests\codeception\unit\models;


use humhub\modules\calendar\interfaces\event\CalendarTypeIF;

class OtherTestEventType implements CalendarTypeIF
{
    const ITEM_TYPE = 'otherTestType';

    public function getKey()
    {
        return static::ITEM_TYPE;
    }

    public function getDefaultColor()
    {
        return null;
    }

    public function getTitle()
    {
        return 'Another Test Type';
    }

    public function getDescription()
    {
        return 'Type only used for testing';
    }

    public function getIcon()
    {
        return null;
    }
}