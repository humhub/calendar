<?php

namespace humhub\modules\calendar\widgets;

use humhub\modules\calendar\CalendarUtils;
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
    public $canWrite = true;
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

        $timeZone = (Yii::$app->user->isGuest) ? Yii::$app->user->getIdentity()->time_zone : date_default_timezone_get();

        return [
            'load-url' => $this->loadUrl,
            'edit-url' => $this->editUrl,
            'drop-url' => $this->dropUrl,
            'enable-url' => Url::to(['/calendar/global/enable']),
            'can-write' => $this->canWrite,
            'editable' => $this->canWrite,
            'selectable' => $this->canWrite,
            'selectHelper' => $this->canWrite,
            'selectors' => $this->selectors,
            'filters' => $this->filters,
            'timezone' => $timeZone,
            'locale' => Yii::$app->formatter->locale,
            'lang' => Yii::$app->language,
            'enabled' => $this->enabled
        ];
    }
}
