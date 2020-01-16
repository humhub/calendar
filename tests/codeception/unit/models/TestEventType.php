<?php


namespace humhub\modules\calendar\tests\codeception\unit\models;


use humhub\modules\calendar\interfaces\event\CalendarTypeIF;

class TestEventType implements CalendarTypeIF
{
    const ITEM_TYPE = 'testType';

    public function getKey()
    {
        static::ITEM_TYPE;
    }

    public function getDefaultColor()
    {
        return '#ffffff';
    }

    public function getTitle()
    {
        return 'Test Type';
    }

    public function getDescription()
    {
        return 'Type only used for testing';
    }

    public function getIcon()
    {
        return 'fa-text-width';
    }
}