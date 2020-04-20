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
use humhub\modules\calendar\permissions\ManageEntry;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\helpers\ContentContainerHelper;
use humhub\modules\user\models\User;
use Yii;
use humhub\modules\calendar\interfaces\CalendarService;
use humhub\widgets\SettingsTabs;

class ContainerConfigMenu extends SettingsTabs
{
    /**
     * @var ContentContainerActiveRecord
     */
    public $contentContainer;

    public function getFirstVisibleItem()
    {
        foreach ($this->items as $item) {
            if(!isset($item['visible']) || $item['visible'] === true) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->contentContainer = ContentContainerHelper::getCurrent();

        if(!$this->contentContainer && !Yii::$app->user->isGuest) {
            $this->contentContainer = Yii::$app->user->identity;
        }

        if($this->contentContainer) {
            $this->initItems();
        }

        parent::init();
    }

    public function initItems()
    {
        /* @var $calendarService CalendarService */
        $calendarService =  Yii::$app->getModule('calendar')->get(CalendarService::class);
        $canConfigure =  $this->contentContainer->can(ManageEntry::class);

        $this->items = [
            [
                'label' => Yii::t('CalendarModule.widgets_GlobalConfigMenu', 'Defaults'),
                'url' => Url::toConfig($this->contentContainer),
                'active' => $this->isCurrentRoute('calendar', 'container-config', 'index'),
                'visible' => $canConfigure
            ],
            [
                'label' => Yii::t('CalendarModule.widgets_GlobalConfigMenu', 'Event Types'),
                'url' => Url::toConfigTypes($this->contentContainer),
                'active' => $this->isCurrentRoute('calendar', 'container-config', 'types'),
                'visible' => $canConfigure
            ],
        ];

        if(!empty($calendarService->getCalendarItemTypes($this->contentContainer))) {
            $this->items[] = [
                'label' => Yii::t('CalendarModule.widgets_GlobalConfigMenu', 'Calendars'),
                'url' => Url::toConfigCalendars($this->contentContainer),
                'active' => $this->isCurrentRoute('calendar', 'container-config', 'calendars'),
                'visible' => $canConfigure
            ];
        }
    }

}