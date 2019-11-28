<?php


namespace humhub\modules\calendar\interfaces;


interface CalendarTypeIF
{
    public function getKey();
    public function getDefaultColor();
    public function getTitle();
    public function getDescription();
    public function getIcon();
}