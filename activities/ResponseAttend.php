<?php

namespace humhub\modules\calendar\activities;

use humhub\modules\activity\components\BaseContentActivity;
use humhub\modules\activity\interfaces\ConfigurableActivityInterface;
use humhub\modules\calendar\models\CalendarDateFormatter;
use humhub\modules\calendar\models\CalendarEntry;
use Yii;

/**
 * @extends BaseContentActivity<CalendarEntry>
 */
class ResponseAttend extends BaseContentActivity implements ConfigurableActivityInterface
{
    protected string $contentActiveRecordClass = CalendarEntry::class;

    public static function getTitle(): string
    {
        return Yii::t('CalendarModule.notification', 'Calendar: attend');
    }

    public static function getDescription(): string
    {
        return Yii::t('CalendarModule.notification', 'Whenever someone participates in an event.');
    }

    protected function getMessage(array $params): string
    {
        $params['dateTime']
            = (new CalendarDateFormatter(['calendarItem' => $this->contentActiveRecord]))
                ->getFormattedTime();

        return Yii::t('CalendarModule.views', '{displayName} is attending {contentTitle} on {dateTime}.', $params);
    }
}
