<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\calendar\models\forms;

use DateInterval;
use DateTime;
use DateTimeZone;
use humhub\libs\DbDateValidator;
use humhub\libs\TimezoneHelper;
use humhub\modules\calendar\CalendarUtils;
use humhub\modules\content\models\Content;
use humhub\modules\space\models\Space;
use Yii;
use humhub\modules\calendar\models\CalendarEntry;
use yii\base\Model;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 12.07.2017
 * Time: 16:14
 */
class CalendarEntryForm extends Model
{

    /**
     * @var integer Content visibility
     */
    public $is_public;

    /**
     * @var string start date submitted by user will be converted to db date format and timezone after validation
     */
    public $start_date;

    /**
     * @var string start time string
     */
    public $start_time;

    /**
     * @var string end date submitted by user will be converted to db date format and timezone after validation
     */
    public $end_date;

    /**
    * @var string end time string
    */
    public $end_time;

    /**
     * @var timeZone set in calendar form
     */
    public $timeZone;

    /**
     * @var cached timeZone option items used for the form dropdown
     */
    public $timeZoneItems;

    /**
     * @var CalendarEntry
     */
    public $entry;

    public function init()
    {
        if($this->entry) {
            $this->populateTime($this->entry->start_datetime, $this->entry->end_datetime, Yii::$app->timeZone);
            $this->is_public = $this->entry->content->visibility;
        }

        $this->timeZone = Yii::$app->user->getIdentity()->time_zone;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['timeZone'], 'in', 'range' => DateTimeZone::listIdentifiers()],
            [['is_public'], 'integer'],
            [['start_time', 'end_time'], 'date', 'format' => 'php:H:i'],
            [['start_date'], DbDateValidator::className(), 'format' => Yii::$app->params['formatter']['defaultDateFormat'], 'timeAttribute' => 'start_time'],
            [['end_date'], DbDateValidator::className(), 'format' => Yii::$app->params['formatter']['defaultDateFormat'], 'timeAttribute' => 'end_time'],
            [['end_date'], 'validateEndTime'],
        ];
    }

    /**
     * Validator for the endtime field.
     * Execute this after DbDateValidator
     *
     * @param string $attribute attribute name
     * @param type $params parameters
     */
    public function validateEndTime($attribute, $params)
    {
        if (new DateTime($this->start_date) >= new DateTime($this->end_date)) {
            $this->addError($attribute, Yii::t('CalendarModule.base', "End time must be after start time!"));
        }
    }

    public function attributeLabels()
    {
        return [
            'start_date' => Yii::t('CalendarModule.base', 'Start Date'),
            'end_date' => Yii::t('CalendarModule.base', 'End Date'),
            'start_time' => Yii::t('CalendarModule.base', 'Start Time'),
            'end_time' => Yii::t('CalendarModule.base', 'End Time'),
            'timeZone' => Yii::t('CalendarModule.base', 'Time Zone'),
            'is_public' => Yii::t('CalendarModule.base', 'Public'),
        ];
    }

    public function createNew($contentContainer, $start = null, $end = null)
    {
        $this->entry = new CalendarEntry();
        $this->entry->content->container = $contentContainer;

        $this->is_public = $this->entry->content->visibility;

        $this->timeZone = Yii::$app->user->getIdentity()->time_zone;

        // Populate start / end time from fullcalendar
        $this->populateTime($start, $end);
    }

    public function load($data, $formName = null)
    {
        parent::load($data);

        $this->entry->content->visibility = $this->is_public;

        // We set the forms timeZone as formatter timeZone
        Yii::$app->formatter->timeZone = $this->timeZone;

        return $this->entry->load($data);
    }

    public function save()
    {
        if(!$this->validate()) {
            return false;
        }

        // After validation the date was translated to system time zone, which we expect in the database.
        $this->entry->start_datetime = $this->start_date;
        $this->entry->end_datetime = $this->end_date;

        // The form expects user time zone, so we translate back from app timeZone to user timezone
        $this->populateTime($this->start_date, $this->end_date, Yii::$app->timeZone);

        return $this->entry->save();
    }

    public function getParticipationModeItems()
    {
        return [
            CalendarEntry::PARTICIPATION_MODE_NONE => Yii::t('CalendarModule.views_entry_edit', 'No participants'),
            CalendarEntry::PARTICIPATION_MODE_ALL => Yii::t('CalendarModule.views_entry_edit', 'Everybody can participate')
        ];
    }

    public function showTimeFields()
    {
        return !$this->entry->all_day;
    }

    public function getTimeZoneItems()
    {
        if(empty($this->timeZoneItems)) {
            $this->timeZoneItems = TimezoneHelper::generateList();
        }

        return $this->timeZoneItems;
    }

    public function getUserTimezoneLabel()
    {
        $entries = $this->getTimeZoneItems();
        $userTimezone = Yii::$app->user->getIdentity()->time_zone;
        return $entries[$userTimezone];
    }

    public function updateTime($start = null, $end = null)
    {
        $this->populateTime($start, $end);
        return $this->save();
    }

    public function populateTime($start = null, $end = null, $timeZone = null)
    {
        if(!$start) {
            return;
        }

        $timeZone = empty($timeZone) ? $this->timeZone : $timeZone;

        $startTime = new DateTime($start, new DateTimeZone($timeZone));
        $endTime = new DateTime($end, new DateTimeZone($timeZone));

        $this->start_date = Yii::$app->formatter->asDateTime($startTime, 'php:Y-m-d');
        $this->start_time = Yii::$app->formatter->asTime($startTime, 'short');

        // Fix FullCalendar EndTime
        if (CalendarUtils::isFullDaySpan($startTime, $endTime, true)) {
            // In Fullcalendar the EndTime is the moment AFTER the event

            $endTime->sub(new DateInterval("PT1S")); // one second
            $this->entry->all_day = 1;
        }

        $this->end_date = Yii::$app->formatter->asDateTime($endTime, 'php:Y-m-d');
        $this->end_time = Yii::$app->formatter->asTime($endTime, 'short');
    }
}