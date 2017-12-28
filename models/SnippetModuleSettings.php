<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\models;

use Yii;
use \yii\base\Model;

/**
 * 
 */
class SnippetModuleSettings extends Model
{
    /**
     * Duration settings in days
     */
    const DURATION_WEEK = 7;
    const DURATION_MONTH = 31;
    const DURATION_HALF_YEAR = 182;
    const DURATION_YEAR = 365;

    /**
     * @var boolean determines if the dashboard widget should be shown or not (default true)
     */
    public $upcomingEventsSnippetShow = true;
    
    /**
     * @var boolean duration of upcoming events for the dashboard widget (default 31 days)
     */
    public $upcomingEventsSnippetDuration = self::DURATION_MONTH;
    
    /**
     * @var int maximum amount of dashboard event items
     */
    public $upcomingEventsSnippetMaxItems = 5;
    
    /**
     * @var int defines the snippet widgets sort order 
     */
    public $upcomingEventsSnippetSortOrder = 0;
    /**
     * @var boolean determines if the calendar top menu item adn dashboard widget should only be shown if the user installed the calendar module in his profile
     */
    public $showIfInstalled = false;

    public function init()
    {
        $module = Yii::$app->getModule('calendar');
        $this->upcomingEventsSnippetShow = $module->settings->get('upcomingEventsSnippetShow', $this->upcomingEventsSnippetShow);
        $this->upcomingEventsSnippetDuration = $module->settings->get('upcomingEventsSnippetDuration', $this->upcomingEventsSnippetDuration);
        $this->upcomingEventsSnippetSortOrder = $module->settings->get('upcomingEventsSnippetSortOrder', $this->upcomingEventsSnippetSortOrder);
        $this->upcomingEventsSnippetMaxItems = $module->settings->get('upcomingEventsSnippetMaxItems', $this->upcomingEventsSnippetMaxItems);
        $this->showIfInstalled = $module->settings->get('showIfInstalled', $this->showIfInstalled);
    }
    
    public function showUpcomingEventsSnippet()
    {
        return $this->upcomingEventsSnippetShow && $this->showGlobalCalendarItems();
    }
    
    public function showGlobalCalendarItems()
    {
        return !self::instantiate()->showIfInstalled || (!Yii::$app->user->isGuest && Yii::$app->user->getIdentity()->isModuleEnabled('calendar'));
    }
    
    /**
     * Static initializer
     * @return \self
     */
    public static function instantiate()
    {
        return new self;
    }
            
    
    /**
     * @inheritDoc
     */
    public function rules()
    {
        return [
            [['upcomingEventsSnippetShow', 'showIfInstalled'],  'boolean'],
            ['upcomingEventsSnippetDuration',  'number', 'min' => self::DURATION_WEEK, 'max' => self::DURATION_YEAR],
            ['upcomingEventsSnippetSortOrder',  'number', 'min' => 0],
            ['upcomingEventsSnippetMaxItems',  'number', 'min' => 1, 'max' => 30]
        ];
    }
    
    /**
     * @inheritDoc
     */
    public function attributeLabels()
    {
        return [
            'upcomingEventsSnippetShow' => Yii::t('CalendarModule.config', "Show snippet"),
            'upcomingEventsSnippetDuration' => Yii::t('CalendarModule.config', 'Interval of upcoming events'),
            'upcomingEventsSnippetSortOrder' => Yii::t('CalendarModule.config', 'Sort order'),
            'upcomingEventsSnippetMaxItems' => Yii::t('CalendarModule.config', 'Max event items'),
            'showIfInstalled' => Yii::t('CalendarModule.config', 'Only show top menu item and snippet if the module is installed in the users profile'),
        ];
    }
    
    public function getDurationItems()
    {
        return [
            self::DURATION_WEEK => Yii::t('CalendarModule.config', 'One week'),
            self::DURATION_MONTH => Yii::t('CalendarModule.config', 'One month'),
            self::DURATION_HALF_YEAR => Yii::t('CalendarModule.config', 'Half a year'),
            self::DURATION_YEAR => Yii::t('CalendarModule.config', 'One year'),
        ];
    }
    
    public function save()
    {
        if(!$this->validate()) {
            return false;
        }
        
        $module = Yii::$app->getModule('calendar');
        $module->settings->set('upcomingEventsSnippetShow', $this->upcomingEventsSnippetShow);
        $module->settings->set('upcomingEventsSnippetDuration', $this->upcomingEventsSnippetDuration);
        $module->settings->set('upcomingEventsSnippetSortOrder', $this->upcomingEventsSnippetSortOrder);
        $module->settings->set('upcomingEventsSnippetMaxItems', $this->upcomingEventsSnippetMaxItems);
        $module->settings->set('showIfInstalled', $this->showIfInstalled);
        return true;
    }
}
