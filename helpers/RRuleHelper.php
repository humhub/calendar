<?php

namespace humhub\modules\calendar\helpers;

use Recurr\Rule;

class RRuleHelper
{

    /**
     * @param $rrule
     * @param $until
     * @return string
     * @throws \Recurr\Exception\InvalidRRule
     */
    public static function setUntil($rrule, $until)
    {
        return (new Rule($rrule))->setUntil($until)->getString(Rule::TZ_FIXED);
    }

    public static function compare($oldRrule, $newRrule, $ignoreUntil = false)
    {
        if($oldRrule === $newRrule) {
            return true;
        }

        $oldRruleArr = static::toArray($oldRrule);
        $newRruleArr = static::toArray($newRrule);

        if($ignoreUntil) {
            $oldRruleArr['UNTIL'] = null;
            $newRruleArr['UNTIL'] = null;
        }

        return $oldRruleArr == $newRruleArr;
    }

    public static function toArray($rrule)
    {
        $rrule  = strtoupper($rrule);
        $rrule  = trim($rrule, ';');
        $rrule  = trim($rrule, "\n");
        $rows   = explode("\n", $rrule);

        $parts = [];

        foreach ($rows as $rruleForRow) {
            $rrule = new Rule();
            $parts = array_merge($parts, $rrule->parseString($rruleForRow));
        }

        return $parts;
    }
}