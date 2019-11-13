<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\calendar\models\forms;

use humhub\modules\calendar\interfaces\recurrence\RecurrenceFormModel;
use humhub\modules\content\widgets\richtext\RichText;
use humhub\modules\topic\models\Topic;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
use DateInterval;
use DateTime;
use DateTimeZone;
use humhub\libs\DbDateValidator;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\models\CalendarEntryType;
use humhub\modules\calendar\models\DefaultSettings;
use humhub\modules\content\models\Content;
use humhub\modules\calendar\models\CalendarEntry;

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
     * @var string timeZone set in calendar form
     */
    public $timeZone;

    /**
     * @var int calendar event type id
     */
    public $type_id;

    /**
     * @var
     */
    public $topics = [];

    /**
     * @var bool
     */
    public $sendUpdateNotification = 0;

    /**
     * @var integer if set to true all space participants will be added to the event
     */
    public $forceJoin = 0;

    /**
     * @var CalendarEntry
     */
    public $entry;

    /**
     * @var ReminderSettings
     */
    public $reminderSettings;

    /**
     * @var RecurrenceFormModel
     */
    public $recurrenceForm;

    /**
     * @param $contentContainer
     * @param null $start
     * @param null $end
     * @return CalendarEntryForm
     * @throws InvalidConfigException
     * @throws \yii\base\Exception
     */
    public static function createEntry($contentContainer, $start = null, $end = null)
    {
        $instance = new static(['entry' => new CalendarEntry($contentContainer)]);
        $instance->updateDateRangeFromCalendar($start, $end);
        return $instance;
    }

    public function init()
    {
        parent::init();
        $this->timeZone = $this->entry->time_zone;
        $this->is_public = $this->entry->content->visibility;

        if(!$this->entry->isNewRecord) {
            $type = $this->entry->getType();
            if ($type) {
                $this->type_id = $type->id;
            }

            $this->topics = $this->entry->content->getTags(Topic::class);

            $this->updateDateRange($this->entry->getStartDateTime(), $this->entry->getEndDateTimeMomentAfter(), !$this->entry->isAllDay());
        } else {
            $this->entry->setDefaults();
        }

        $this->reminderSettings = new ReminderSettings(['entry' => $this->entry]);
        $this->recurrenceForm = new RecurrenceFormModel(['entry' => $this->entry]);

    }

    public function updateDateRangeFromCalendar($start, $end, $save = false)
    {
        if(!$start || !$end) {
            return;
        }

        $this->updateDateRange($start, $end);
        $this->updateEntryDates();
        if($save) {
            return $this->save();
        }

        return true;
    }

    public function updateDateRange($start, $end, $translateTimeZone = false)
    {
        if (!$start || !$end) {
            return;
        }

        if($translateTimeZone) {
            $start = CalendarUtils::translateTimezone($start, CalendarUtils::getSystemTimeZone(), $this->timeZone);
            $end = CalendarUtils::translateTimezone($end, CalendarUtils::getSystemTimeZone(), $this->timeZone);
        }

        $this->start_date = CalendarUtils::getDate($start);
        $this->start_time = CalendarUtils::getTime($start, $this->getTimeFormat());
        $this->end_date = CalendarUtils::getDate($end);
        $this->end_time = CalendarUtils::getTime($end, $this->getTimeFormat());
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['timeZone', 'in', 'range' => DateTimeZone::listIdentifiers()],
            ['topics', 'safe'],
            [['is_public', 'type_id', 'sendUpdateNotification', 'forceJoin'], 'integer'],
            [['start_time', 'end_time'], 'date', 'type' => 'time', 'format' => $this->getTimeFormat()],
            ['start_date', 'validateDate', 'params' => ['timeField' => 'start_time']],
            ['end_date', 'validateDate', 'params' => ['timeField' => 'end_time']],
            ['end_date', 'validateEndTime'],
            ['type_id', 'validateType'],
        ];
    }

    public function validateDate($attribute, $params)
    {
        $timeField = $params['timeField'];
        $value = $this->$attribute;
        $timeValue = $this->$timeField;

        try {
            $parsed = CalendarUtils::parseDateTimeString($value, $timeValue, $this->getTimeFormat());
            if (empty($parsed)) {
                throw new \Exception('Invalid date time format: ' . $value . 'with time: ' . $timeValue);
            }
        } catch (\Exception $e) {
            $this->addError($attribute, Yii::t('CalendarModule.base', 'Invalid date format!'));
            Yii::warning($e);
        }
    }


    public function getTimeFormat()
    {
        return Yii::$app->formatter->isShowMeridiem() ? 'php:h:i a' : 'php:H:i';
    }

    public function beforeValidate()
    {
        //$this->checkAllDay();
        return parent::beforeValidate();
    }

    public function checkAllDay()
    {
        Yii::$app->formatter->timeZone = $this->timeZone;
        if ($this->entry->all_day) {
            $date = new DateTime('now', new DateTimeZone($this->timeZone));
            $date->setTime(0, 0);
            $this->start_time = Yii::$app->formatter->asTime($date, $this->getTimeFormat());
            $date->setTime(23, 59, 59);
            $this->end_time = Yii::$app->formatter->asTime($date, $this->getTimeFormat());
        }
        Yii::$app->i18n->autosetLocale();
    }

    /**
     * Validator for the endtime field.
     * Execute this after DbDateValidator
     *
     * @param string $attribute attribute name
     * @param [] $params parameters
     * @throws \Exception
     */
    public function validateEndTime($attribute, $params)
    {
        if ($this->getStartDateTime() >= $this->getEndDateTime()) {
            $this->addError($attribute, Yii::t('CalendarModule.base', "End time must be after start time!"));
        }
    }

    public function getStartDateTime()
    {
        return CalendarUtils::parseDateTimeString($this->start_date, $this->start_time, $this->getTimeFormat(), $this->timeZone);
    }

    public function getEndDateTime()
    {
        return CalendarUtils::parseDateTimeString($this->end_date, $this->end_time, $this->getTimeFormat(), $this->timeZone);
    }

    public function validateType($attribute, $params)
    {
        if (!$this->type_id) {
            return;
        }

        $type = CalendarEntryType::findOne(['id' => $this->type_id]);

        if ($type->contentcontainer_id != null && $type->contentcontainer_id !== $this->entry->content->contentcontainer_id) {
            $this->addError($attribute, Yii::t('CalendarModule.base', "Invalid event type id selected."));
        }
    }

    public function attributeLabels()
    {
        return [
            'start_date' => Yii::t('CalendarModule.base', 'Start Date'),
            'type_id' => Yii::t('CalendarModule.base', 'Event Type'),
            'end_date' => Yii::t('CalendarModule.base', 'End Date'),
            'start_time' => Yii::t('CalendarModule.base', 'Start Time'),
            'end_time' => Yii::t('CalendarModule.base', 'End Time'),
            'timeZone' => Yii::t('CalendarModule.base', 'Time Zone'),
            'is_public' => Yii::t('CalendarModule.base', 'Public'),
            'sendUpdateNotification' => Yii::t('CalendarModule.base', 'Send update notification'),
            'forceJoin' => ($this->entry->isNewRecord)
                ? Yii::t('CalendarModule.base', 'Add all space members to this event')
                : Yii::t('CalendarModule.base', 'Add remaining space members to this event'),
        ];
    }

    public function load($data, $formName = null)
    {
        // Make sure we load the timezone beforehand so its available in validators etc..
        if ($data && isset($data[$this->formName()]) && isset($data[$this->formName()]['timeZone']) && !empty($data[$this->formName()]['timeZone'])) {
            $this->timeZone = $data[$this->formName()]['timeZone'];
        }

        if (parent::load($data) && !empty($this->timeZone)) {
            $this->entry->time_zone = $this->timeZone;
        }

        $this->entry->content->visibility = $this->is_public;

        if (!$this->entry->load($data)) {
            return false;
        }

        // change 0, '' etc to null
        if (empty($this->type_id)) {
            $this->type_id = null;
        }

        $this->reminderSettings->load($data);
        $this->recurrenceForm->load($data);

        $this->updateEntryDates();

        return true;
    }

    private function updateEntryDates()
    {
        $startDt = $this->getStartDateTime();
        $endDt =  $this->getEndDateTime();

        $this->entry->all_day = (int) CalendarUtils::isAllDay($startDt, $endDt);

        if($this->entry->isAllDay()) {
            // Translate from moment after to non moment after
            $endDt->modify('- 1 second');
            $this->entry->start_datetime = CalendarUtils::toDBDateFormat($startDt);
            $this->entry->end_datetime = CalendarUtils::toDBDateFormat($endDt);
        } else {
            $this->entry->start_datetime = CalendarUtils::translateToSystemTimezone($startDt, $this->timeZone);
            $this->entry->end_datetime = CalendarUtils::translateToSystemTimezone($endDt, $this->timeZone);
        }
    }

    /**
     * @return bool
     * @throws \Throwable
     */
    public function save()
    {
        if (!$this->validate()) {
            return false;
        }

        // The form expects user time zone, so we translate back from app to user timezone
        //$this->translateDateTimes($this->entry->start_datetime, $this->entry->end_datetime, Yii::$app->timeZone, $this->timeZone);

        return CalendarEntry::getDb()->transaction(function ($db) {
            if ($this->entry->save()) {
                RichText::postProcess($this->entry->description, $this->entry);
                RichText::postProcess($this->entry->participant_info, $this->entry);

                if ($this->type_id !== null) {
                    $this->entry->setType($this->type_id);
                }

                if ($this->sendUpdateNotification && !$this->entry->isNewRecord) {
                    $this->entry->participation->sendUpdateNotification();
                }

                if ($this->forceJoin) {
                    $this->entry->participation->addAllUsers();
                }

                Topic::attach($this->entry->content, $this->topics);

                return $this->recurrenceForm->save() && $this->reminderSettings->save();
            }

            return false;
        });
    }

    public static function getParticipationModeItems()
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
}