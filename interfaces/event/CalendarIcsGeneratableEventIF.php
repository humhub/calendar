<?php

namespace humhub\modules\calendar\interfaces\event;

interface CalendarIcsGeneratableEventIF
{
    public function generateIcs(): ?string;
}
