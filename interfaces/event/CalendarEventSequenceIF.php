<?php


namespace humhub\modules\calendar\interfaces\event;


interface CalendarEventSequenceIF
{
    public function getSequence();

    public function setSequence($sequence);
}