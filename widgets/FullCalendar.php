<?php

namespace humhub\modules\calendar\widgets;

use humhub\modules\calendar\CalendarUtils;
use humhub\modules\calendar\permissions\CreateEntry;
use humhub\widgets\JsWidget;
use Yii;
use yii\helpers\Url;

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
    public $loadUrl;
    public $dropUrl;
    public $editUrl;
    public $selectors = [];
    public $filters = [];
    public $contentContainer;
    public $enabled = true;

    public function init()
    {
        \humhub\modules\calendar\assets\Assets::register($this->getView());

        if(Yii::$app->user->isGuest) {
            $this->canWrite = false;
            $this->enabled = false;
            parent::init();
            return;
        }

        if(!$this->contentContainer) {
            $this->contentContainer = Yii::$app->user->getIdentity();
        }

        // Used by the global calendar if the module is not enabled for the given user.
        if($this->contentContainer && !$this->contentContainer->isModuleEnabled('calendar')) {
            $this->enabled = false;
        }


        $this->editUrl = $this->contentContainer->createUrl('/calendar/entry/edit', ['cal' => true]);
        $this->dropUrl = $this->contentContainer->createUrl('/calendar/entry/edit-ajax');
        parent::init();
    }
    
    public function getData()
    {
        return [
            'load-url' => $this->loadUrl,
            'edit-url' => $this->editUrl,
            'drop-url' => $this->dropUrl,
            'enable-url' => Url::to(['/calendar/global/enable']),
            'enabled' => $this->enabled,
            'can-write' => $this->canWrite,
            'can-create' => $this->canCreate(),
            'editable' => $this->canWrite,
            'selectable' => $this->canWrite,
            'select-helper' => $this->canWrite,
            'selectors' => $this->selectors,
            'filters' => $this->filters,
            'timezone' =>  Yii::$app->formatter->timeZone,
            'locale' => $this->translateLocale(Yii::$app->formatter->locale),
        ];
    }

    const LOCALE_MAPPING = [
        'nb-no' => 'nb',
        'fa-ir' => 'fa',
    ];

    private function translateLocale($locale)
    {
        $locale = str_replace('_', '-', $locale);
        if(array_key_exists($locale, self::LOCALE_MAPPING)) {
            $locale = self::LOCALE_MAPPING[$locale];
        }

        return $locale;
    }

    private function canCreate()
    {
        if($this->contentContainer && !Yii::$app->user->isGuest) {
            return $this->contentContainer->can(CreateEntry::class);
        } else if(!Yii::$app->user->isGuest) {
            return Yii::$app->user->getIdentity()->isCurrentUser();
        }

        return false;
    }


}
