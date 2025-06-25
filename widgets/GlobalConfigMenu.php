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
 * Date: 17.07.2017
 * Time: 21:02
 */

namespace humhub\modules\calendar\widgets;

use humhub\modules\calendar\helpers\Url;
use Yii;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\widgets\SettingsTabs;

class GlobalConfigMenu extends SettingsTabs
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        /* @var $calendarService CalendarService */
        $calendarService =  Yii::$app->getModule('calendar')->get(CalendarService::class);

        $this->items = [
            [
                'label' => Yii::t('CalendarModule.views', 'Defaults'),
                'url' => Url::toConfig(),
                'active' => $this->isCurrentRoute('calendar', 'config', 'index'),
                'sortOrder' => 10,
            ],
            [
                'label' => Yii::t('CalendarModule.views', 'Event Types'),
                'url' =>  Url::toConfigTypes(),
                'active' => $this->isCurrentRoute('calendar', 'config', 'types'),
                'sortOrder' => 20,
            ],
            [
                'label' => Yii::t('CalendarModule.views', 'Snippet'),
                'url' =>  Url::toConfigSnippets(),
                'active' => $this->isCurrentRoute('calendar', 'config', 'snippet'),
                'sortOrder' => 30,
            ],
            [
                'label' => Yii::t('CalendarModule.views', 'Menu'),
                'url' =>  Url::toConfigMenu(),
                'active' => $this->isCurrentRoute('calendar', 'config', 'menu'),
                'sortOrder' => 40,
            ],
            [
                'label' => Yii::t('CalendarModule.views', 'Export'),
                'url' =>  Url::toExportMenu(),
                'active' => $this->isCurrentRoute('calendar', 'config', 'export'),
                'sortOrder' => 40,
            ],
        ];

        if (!empty($calendarService->getCalendarItemTypes())) {
            $this->items[] = [
                'label' => Yii::t('CalendarModule.views', 'Calendars'),
                'url' => Url::toConfigCalendars(),
                'active' => $this->isCurrentRoute('calendar', 'config', 'calendars'),
                'sortOrder' => 25,
            ];
        }

        parent::init();
    }

}
