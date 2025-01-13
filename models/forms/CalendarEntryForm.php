<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\calendar\models\forms;

use DateTime;
use humhub\modules\calendar\models\reminder\forms\ReminderSettings;
use humhub\modules\calendar\Module;
use humhub\modules\content\models\Content;
use humhub\modules\content\permissions\CreatePublicContent;
use humhub\modules\space\models\Space;
use Yii;
use yii\base\Exception;
use yii\base\Model;
use DateTimeZone;
use humhub\modules\calendar\interfaces\recurrence\RecurrenceFormModel;
use humhub\modules\calendar\models\forms\validators\CalendarDateFormatValidator;
use humhub\modules\calendar\models\forms\validators\CalendarEndDateValidator;
use humhub\modules\calendar\models\forms\validators\CalendarTypeValidator;
use humhub\modules\content\widgets\richtext\RichText;
use humhub\modules\topic\models\Topic;
use humhub\modules\calendar\helpers\CalendarUtils;
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
     * @var int Content visibility
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
     * @var CalendarEntry
     */
    public $entry;

    /**
     * @var CalendarEntry
     */
    public $original;

    /**
     * @var bool
     */
    public $reminder;

    /**
     * @var ReminderSettings
     */
    public $reminderSettings;

    /**
     * @var bool
     */
    public $recurring;

    /**
     * @var RecurrenceFormModel
     */
    public $recurrenceForm;

    /**
     * @var bool defines if the Task is created from wall stream
     */
    public $wall;

    /**
     * @var ?int Hide a task on the wall stream
     */
    public ?int $hidden = null;

    /**
     * Will create a new CalendarEntryForm instance with new CalendarEntry model.
     *
     * @param $contentContainer
     * @param string|null $start FullCalendar start datetime e.g.: 2020-01-01 00:00:00
     * @param string|null $end FullCalendar end datetime e.g.: 2020-01-02 00:00:00
     * @param string|null $view FullCalendar view mode, 'month'
     * @param bool $wall True when a Calendary Entry is created/updated from wall stream
     * @return CalendarEntryForm
     * @throws Exception
     */
    public static function createEntry($contentContainer, $start = null, $end = null, $view = null, $wall = null)
    {
        $instance = new static(['entry' => new CalendarEntry($contentContainer)]);
        $instance->updateDateRangeFromCalendar($start, $end, null, false, $view);
        $instance->setDefaults(); // Make sure default values are based on new start/end
        $instance->wall = $wall;
        return $instance;
    }

    public function init()
    {
        parent::init();
        $this->setDefaults();

    }

    private function setDefaults()
    {
        $this->timeZone = $this->entry->time_zone;
        $this->is_public = $this->entry->content->visibility;

        if (!$this->entry->isNewRecord) {
            $type = $this->entry->getEventType();
            if ($type) {
                $this->type_id = $type->id;
            }

            $this->topics = $this->entry->content->getTags(Topic::class);

            $this->setFormDatesFromModel();
            $this->original = CalendarEntry::findOne(['id' => $this->entry->id]);
            $this->hidden = $this->entry->content->hidden;
        } else {
            $this->entry->setDefaults();
            $this->hidden = (new BasicSettings(['contentContainer' => $this->entry->content->getContainer()]))->contentHiddenDefault;
        }

        $this->reminderSettings = new ReminderSettings(['entry' => $this->entry]);
        $this->reminder = $this->entry->isNewRecord && $this->reminderSettings->hasDefaults() ? true : $this->entry->reminder;

        $this->recurring = $this->entry->recurring;
        $this->recurrenceForm = new RecurrenceFormModel(['entry' => $this->entry]);
    }

    /**
     * Updates the form date range from calendar date strings.
     * This function will update the $start_date, $end_date, $start_time, $end_time and the model dates.
     *
     * In order to translate the given date times to the entry timezone, the $timeZone parameter can be used, which
     * defines the timezone of $start and $end date string. This allows calendar updates from users in another timezone
     * than the entries timezone.
     *
     * @param string|null $start FullCalendar start datetime e.g.: 2020-01-01 00:00:00
     * @param string|null $end FullCalendar end datetime e.g.: 2020-01-02 00:00:00
     * @param null $timeZone the timezone of $start/$end, if null $this->timeZone is assumed
     * @param bool $save
     * @param string|null $view FullCalendar view mode, 'month'
     * @return bool|void
     * @throws \Throwable
     */
    public function updateDateRangeFromCalendar($start = null, $end = null, $timeZone = null, $save = false, $view = null)
    {
        if (!$start || !$end) {
            return;
        }

        $startDT = CalendarUtils::getDateTime($start);
        $endDT = CalendarUtils::getDateTime($end);

        if ($view === 'month') {
            $startDT->setTime(date('H') + 1, 0);
            $endDT = (clone $startDT)->modify('+1 hour');
            $this->entry->all_day = 0;
        } else {
            $this->entry->all_day = (int) CalendarUtils::isAllDay($start, $endDT);
        }

        if ($this->isAllDay()) {
            $this->translateFromMomentAfterToFormEndDate($endDT);
        } elseif (!empty($timeZone)) {
            $startDT = CalendarUtils::translateTimezone($startDT, $timeZone, $this->timeZone);
            $endDT = CalendarUtils::translateTimezone($endDT, $timeZone, $this->timeZone);
        }

        $this->setFormDates($startDT, $endDT);

        if ($save) {
            return $this->save();
        }

        return true;
    }

    private function translateFromMomentAfterToFormEndDate(DateTime $dt)
    {
        if (!$this->isAllDay()) {
            return $dt;
        }
        return $dt->modify('-1 day')->setTime(0, 0, 0);
    }

    private function translateFromFormToMomentAfterEndDate(DateTime $dt)
    {
        if (!$this->isAllDay()) {
            return $dt;
        }
        return $dt->modify('+1 day')->setTime(0, 0, 0);
    }

    public function setDefaultTime()
    {
        if ($this->isAllDay()) {
            $withMeridiam = Yii::$app->formatter->isShowMeridiem();
            $this->start_time = $withMeridiam ? '10:00 AM' : '10:00';
            $this->end_time =  $withMeridiam ? '12:00 PM' : '12:00';
        }
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['timeZone', 'in', 'range' => DateTimeZone::listIdentifiers()],
            [['topics', 'reminder', 'recurring'], 'safe'],
            [['is_public', 'hidden',  'type_id', 'sendUpdateNotification'], 'integer'],
            [['start_date', 'end_date'], 'required'],
            [['start_time', 'end_time'], 'date', 'type' => 'time', 'format' => CalendarUtils::getTimeFormat()],
            ['start_date', CalendarDateFormatValidator::class, 'timeField' => 'start_time'],
            ['end_date', CalendarDateFormatValidator::class, 'timeField' => 'end_time'],
            ['end_date', CalendarEndDateValidator::class],
            ['type_id', CalendarTypeValidator::class],
        ];
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
            'location' => Yii::t('CalendarModule.base', 'Location'),
            'is_public' => Yii::t('CalendarModule.base', 'Public'),
            'recurring' => Yii::t('CalendarModule.base', 'Recurring'),
            'reminder' => Yii::t('CalendarModule.base', 'Enable Reminder'),
            'topics' => Yii::t('TopicModule.base', 'Topics'),
            'sendUpdateNotification' => Yii::t('CalendarModule.base', 'Notify participants about changes'),
        ];
    }

    /**
     * @throws \Exception
     */
    public function setFormDatesFromModel()
    {
        $endDt = $this->translateFromMomentAfterToFormEndDate($this->entry->getEndDateTime());
        $this->setFormDates($this->entry->getStartDateTime(), $endDt, !$this->entry->isAllDay());
    }

    /**
     * @param $start
     * @param $end
     * @param bool $translateTimeZone
     * @throws \Exception
     */
    protected function setFormDates($start, $end, $translateTimeZone = false)
    {
        if (!$start || !$end) {
            return;
        }

        $startDt = CalendarUtils::getDateTime($start);
        $endDt = CalendarUtils::getDateTime($end);

        if ($translateTimeZone) {
            $startDt = CalendarUtils::translateTimezone($startDt, CalendarUtils::getSystemTimeZone(), $this->timeZone);
            $endDt = CalendarUtils::translateTimezone($endDt, CalendarUtils::getSystemTimeZone(), $this->timeZone);
        }

        $this->start_date = CalendarUtils::getDateString($startDt);
        $this->start_time = CalendarUtils::getTimeString($startDt, CalendarUtils::getTimeFormat());
        $this->end_date = CalendarUtils::getDateString($endDt);
        $this->end_time = CalendarUtils::getTimeString($endDt, CalendarUtils::getTimeFormat());

        $this->syncModelDatesFromForm();
    }

    private function syncModelDatesFromForm()
    {
        $startDt = $this->getStartDateTime();
        $endDt =  $this->getEndDateTime();

        if ($this->entry->isAllDay()) {
            $this->entry->start_datetime = CalendarUtils::toDBDateFormat($startDt);
            $this->entry->end_datetime = CalendarUtils::toDBDateFormat($this->translateFromFormToMomentAfterEndDate($endDt));
        } else {
            $this->entry->start_datetime = CalendarUtils::translateToSystemTimezone($startDt, $this->timeZone);
            $this->entry->end_datetime = CalendarUtils::translateToSystemTimezone($endDt, $this->timeZone);
        }
    }

    public function canCreatePublicEntry()
    {
        $container = $this->entry->content->container;
        return $container->can(CreatePublicContent::class) && !($container instanceof Space && $container->visibility === Space::VISIBILITY_NONE);
    }

    /**
     * @param array $data
     * @param null $formName
     * @return bool
     * @throws \Throwable
     */
    public function load($data, $formName = null)
    {
        if (empty($data)) {
            return false;
        }

        if (parent::load($data) && !empty($this->timeZone)) {
            $this->entry->time_zone = $this->timeZone;
        }

        $container = $this->entry->content->container;
        if (!$this->canCreatePublicEntry()) {
            $this->entry->content->visibility = Content::VISIBILITY_PRIVATE;
        } else {
            $this->entry->content->visibility = $this->is_public;
        }

        $this->entry->content->hidden = $this->hidden;

        $result = $this->entry->load($data);

        if (empty($this->type_id)) {
            $this->type_id = null;
        }

        if ($this->isAllDay()) {
            $this->start_time = null;
            $this->end_time = null;
        }

        $this->start_date = $this->normalizeFormattedDate($this->start_date);
        $this->end_date = $this->normalizeFormattedDate($this->end_date);

        $startDT = $this->getStartDateTime();
        $endDt = $this->getEndDateTime();

        // Translate from 01.01.20 -> db date format
        $this->setFormDates($startDT, $endDt);

        $result |= $this->reminderSettings->load($data);

        $result |= $this->recurrenceForm->load($data);

        return (bool) $result;
    }

    private function handleDateValidationError()
    {
        $isStartDateError = !empty($this->getErrors('start_date'));
        $isEndDateError = !empty($this->getErrors('end_date'));

        if (!$isStartDateError && !$isEndDateError) {
            return;
        }

        $startDate = $this->getStartDateTime();
        $endDate = $this->getEndDateTime();

        if (!$startDate) {
            if ($this->original) {
                $startDate = $this->original->getStartDateTime();
            } elseif ($endDate instanceof DateTime) {
                $startDate = clone $endDate;
            } else {
                $startDate = new DateTime();
            }
        }

        $endDate = $this->getEndDateTime();
        if (!$endDate) {
            if ($this->original) {
                $endDate = $this->translateFromMomentAfterToFormEndDate($this->original->getEndDateTime());
            } elseif ($startDate instanceof DateTime) {
                $endDate = clone $startDate;
            } else {
                $endDate = new DateTime();
            }

            if ($endDate < $startDate) {
                $endDate = $startDate;
            }
        }

        // Backup the original time
        $startTime = $this->start_time;
        $endTime = $this->end_time;

        $this->setFormDates($startDate, $endDate, !$this->isAllDay());

        $this->start_time = $startTime;
        $this->end_time = $endTime;
    }

    public function isFutureEvent(): bool
    {
        return $this->entry->getStartDateTime() > new DateTime();
    }

    public function showReminderTab(): bool
    {
        return ($this->reminder && $this->isFutureEvent()) || $this->entry->reminder;
    }

    public function showRecurrenceTab(): bool
    {
        return $this->recurring && Module::isRecurrenceActive();
    }

    /**
     * @return bool
     * @throws \Throwable
     */
    public function save()
    {
        if (!$this->validate()) {
            $this->handleDateValidationError();
            return false;
        }

        // The form expects user time zone, so we translate back from app to user timezone
        //$this->translateDateTimes($this->entry->start_datetime, $this->entry->end_datetime, Yii::$app->timeZone, $this->timeZone);

        return CalendarEntry::getDb()->transaction(function ($db) {

            if (!$this->entry->saveEvent()) {
                return false;
            }

            // Patch for https://github.com/humhub/humhub/issues/4847 in 1.8.beta1
            if ($this->entry->description) {
                RichText::postProcess($this->entry->description, $this->entry);
            }

            $this->entry->setType($this->type_id);

            Topic::attach($this->entry->content, $this->topics);

            if ($this->sendUpdateNotification && !$this->entry->isNewRecord && !$this->entry->closed) {
                $this->entry->participation->sendUpdateNotification();
            }

            if (!$this->reminder) {
                $this->reminderSettings->reminderType = ReminderSettings::REMINDER_TYPE_NONE;
                $this->reminderSettings->reminders = [];
            }
            $result = $this->reminderSettings->save();

            if (!$this->recurring) {
                $this->recurrenceForm->frequency = RecurrenceFormModel::FREQUENCY_NEVER;
            }
            $result = $this->recurrenceForm->save($this->original) && $result;

            if ($result) {
                $this->sequenceCheck();
            }

            return $result;
        });
    }

    public function sequenceCheck()
    {
        if (!$this->original) {
            return;
        }

        if ($this->original->getTitle() !== $this->entry->getTitle() ||
            $this->original->getDescription() !== $this->entry->getDescription() ||
            $this->original->getStartDateTime() != $this->entry->getStartDateTime() ||
            $this->original->getEndDateTime() != $this->entry->getEndDateTime() ||
            $this->original->isAllDay() !== $this->entry->isAllDay() ||
            $this->original->participation_mode !== $this->entry->participation_mode ||
            $this->original->getColor() !== $this->entry->getColor() ||
            $this->original->allow_decline !== $this->entry->allow_decline ||
            $this->original->allow_maybe !== $this->entry->allow_maybe ||
            $this->original->getTimezone() !== $this->entry->getTimezone() ||
            $this->original->participant_info !== $this->entry->participant_info ||
            $this->original->getEventStatus() !== $this->entry->getEventStatus() ||
            $this->original->max_participants !== $this->entry->max_participants ||
            $this->original->getRrule() !== $this->entry->getRrule() ||
            $this->original->getExdate() !== $this->entry->getExdate() ||
            $this->original->getLocation() !== $this->entry->getLocation()
        ) {
            CalendarUtils::incrementSequence($this->entry);
            $this->entry->saveEvent();
        }
    }

    public function showTimeFields()
    {
        return !$this->entry->all_day;
    }

    public function isAllDay()
    {
        return (bool) $this->entry->all_day;
    }

    public function getStartDateTime()
    {
        $timeZone = $this->isAllDay() ? 'UTC' : $this->timeZone;
        $startDT = CalendarUtils::parseDateTimeString($this->start_date, $this->start_time, null, $timeZone);

        if ($startDT && $this->isAllDay()) {
            $startDT->setTime(0, 0, 0);
        }
        return $startDT;
    }

    public function getEndDateTime()
    {
        $timeZone = $this->isAllDay() ? 'UTC' : $this->timeZone;
        $endDT = CalendarUtils::parseDateTimeString($this->end_date, $this->end_time, null, $timeZone);
        if ($endDT && $this->isAllDay()) {
            $endDT->setTime(0, 0, 0);
        }
        return $endDT;
    }

    private function normalizeFormattedDate($formattedDate)
    {
        /**
         * If the locale is 'bg' (Bulgarian), remove the 'г.' suffix from the date string.
         * This suffix is automatically added by IntlDateFormatter::format() to indicate "year" in Bulgarian,
         * but IntlDateFormatter::parse() fails to handle it properly, causing a parsing error.
         * To ensure successful parsing, we normalize the date string by removing 'г.'.
         */
        if (Yii::$app->formatter->locale == 'bg') {
            return str_replace(' г.', '', $formattedDate);
        }

        return $formattedDate;
    }
}
