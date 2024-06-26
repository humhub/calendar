<?php

namespace humhub\modules\calendar\widgets;

use humhub\modules\calendar\assets\CalendarAsset;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\helpers\Url;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\participation\FullCalendarSettings;
use humhub\modules\calendar\permissions\CreateEntry;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\widgets\JsWidget;
use Yii;

/**
 * Description of FullCalendarWidget
 *
 * @author luke
 */
class FullCalendar extends JsWidget
{
    public $jsWidget = 'calendar.Calendar';
    public $id = 'calendar';
    public $init = true;
    public $canWrite = false;
    public $isGlobal = false;
    public $loadUrl;
    public $dropUrl;
    public $editUrl;
    public $selectors = [];
    public $filters = [];
    public $types = [];
    /**
     * @var ContentContainerActiveRecord $contentContainer
     */
    public $contentContainer;
    public $enabled = true;
    public $aspectRatio;
    public $height = 'auto';

    public function init()
    {
        CalendarAsset::register($this->getView());

        if (Yii::$app->user->isGuest) {
            $this->canWrite = false;
            $this->enabled = false;
            parent::init();
            return;
        }

        if (!$this->contentContainer) {
            $this->contentContainer = Yii::$app->user->getIdentity();
            $this->isGlobal = true;
        }

        // Used by the global calendar if the module is not enabled for the given user.
        if ($this->contentContainer && !$this->contentContainer->moduleManager->isEnabled('calendar')) {
            $this->enabled = false;
        }

        if ($this->contentContainer) {
            $this->editUrl = Url::toFullCalendarEdit($this->contentContainer);
        }

        parent::init();
    }

    public function getData()
    {
        $defaultSettings = new FullCalendarSettings(['contentContainer' => $this->contentContainer]);

        return [
            'default-view' => $defaultSettings->viewMode,
            'height' => $this->height,
            'load-url' => $this->loadUrl,
            'aspect-ratio' => $this->aspectRatio,
            'edit-url' => $this->editUrl,
            'drop-url' => $this->dropUrl,
            'global-create-url' => Url::toGlobalCreate(),
            'global' => (int)$this->isGlobal,
            'can-write' => (int)$this->canWrite,
            'can-create' => (int)$this->canCreate(),
            'editable' => (int)$this->canWrite,
            'selectable' => (int)$this->canWrite,
            'select-helper' => (int)$this->canWrite,
            'selectors' => $this->selectors,
            'filters' => $this->filters,
            'types' => $this->types,
            'time-zone' => CalendarUtils::getUserTimeZone(true),
            'locale' => $this->translateLocale(Yii::$app->formatter->locale),
        ];
    }

    public const LOCALE_MAPPING = [
        'nb-no' => 'nb',
        'fa-ir' => 'fa',
    ];

    private function translateLocale($locale)
    {
        $locale = str_replace('_', '-', $locale);
        if (array_key_exists($locale, self::LOCALE_MAPPING)) {
            $locale = self::LOCALE_MAPPING[$locale];
        }

        return $locale;
    }

    private function canCreate()
    {
        if ($this->contentContainer && !Yii::$app->user->isGuest) {
            return (new CalendarEntry($this->contentContainer))->content->canEdit();
        } elseif (!Yii::$app->user->isGuest) {
            return Yii::$app->user->getIdentity()->isCurrentUser();
        }

        return false;
    }


}
