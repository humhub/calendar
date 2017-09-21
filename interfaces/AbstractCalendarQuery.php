<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\calendar\interfaces;

use DateInterval;
use Exception;
use humhub\modules\content\components\ContentContainerActiveRecord;
use Yii;
use DateTime;
use humhub\modules\user\models\User;
use humhub\modules\content\components\ActiveQueryContent;
use yii\base\Object;

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 14.09.2017
 * Time: 12:31
 */

abstract class AbstractCalendarQuery extends Object
{
    /**
     * @var string Defines the ActiveRecord class used for this query
     */
    protected static $recordClass;

    /**
     * @var string database field for start date
     */
    public $startField = 'start_datetime';

    /**
     * @var string database field for end date
     */
    public $endField = 'end_datetime';

    /**
     * @var string database date format
     */
    public $dateFormat = 'Y-m-d H:i:s';

    /**
     * Available filters
     */
    const FILTER_PARTICIPATE = 1;
    const FILTER_INVITED = 2;
    const FILTER_NOT_RESPONDED = 3;
    const FILTER_RESPONDED = 4;
    const FILTER_MINE = 5;
    const FILTER_USERRELATED = 'userRelated';

    /**
     * @var array Activated query filters
     */
    protected $_filters = [];

    /**
     * @var \yii\db\ActiveQuery the actual query instance
     */
    protected $_query;

    /**
     * @var \humhub\modules\user\models\User user instance used for some of the filters e.g. mine() filter
     */
    protected $_user;

    /**
     * @var DateTime start date of the filter interval
     */
    protected $_from;

    /**
     * @var DateTime end date of the filter interval
     */
    protected $_to;

    /**
     * @var boolean flag to enable/disable the openRange behaviour (default true)
     */
    protected $_openRange = true;

    /**
     * @var string query order string
     */
    protected $_orderBy;

    /**
     * @var int query limit
     */
    protected $_limit = 0;

    /**
     * @var \humhub\modules\content\components\ContentContainerActiveRecord used to filter entries by contentContainer
     */
    protected $_container;

    /**
     * @var array user related scopes used for [[userRelated()]] filters
     */
    protected $_userScopes;

    /**
     * @var boolean if set to false (default) will ignore time information in date filter intervals
     */
    protected $_withTime = false;

    /**
     * @var boolean determines if the query was already built
     */
    protected $_built = false;

    /**
     * @param DateTime $start
     * @param DateTime $end
     * @param ContentContainerActiveRecord $container
     * @param array $filters
     * @param int $limit
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function findForFilter(DateTime $start, DateTime $end, ContentContainerActiveRecord $container = null, $filters = [], $limit = 50)
    {
        return static::find()
            ->container($container)
            ->from($start)->to($end)
            ->filter($filters)
            ->limit($limit)->all();
    }

    /**
     * @param CalendarItemsEvent $event
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function findForEvent(CalendarItemsEvent $event)
    {
        return static::findForFilter($event->start, $event->end, $event->contentContainer, $event->filters, $event->limit);
    }


    /**
     * Static initializer.
     * @param User $user user instance used for some of the filter e.g. [[mine()]] by default current logged in user.
     * @return \self
     */
    public static function find(User $user = null)
    {
        if (!$user && !Yii::$app->user->isGuest) {
            $user = Yii::$app->user->getIdentity();
        }

        $instance = new static();
        $instance->_query = call_user_func(static::$recordClass .'::find');
        $instance->_user = $user;

        return $instance;
    }

    /**
     * Filters user related entries by means of the given scope as.
     * If no scope is given this method will fitler entries with the following scope:
     *
     *  - ActiveQueryContent::USER_RELATED_SCOPE_OWN_PROFILE
     *  - ActiveQueryContent::USER_RELATED_SCOPE_SPACES
     *
     * @param int|array $scopes user related filter scopes
     * @return $this
     * @see ActiveQueryContent::userRelated()
     */
    public function userRelated($scopes = [ActiveQueryContent::USER_RELATED_SCOPE_OWN_PROFILE, ActiveQueryContent::USER_RELATED_SCOPE_SPACES])
    {
        if (!empty($scopes)) {
            $scopes = (is_array($scopes)) ? $scopes : [$scopes];
            $this->_userScopes = $scopes;
        }

        return $this;
    }

    /**
     * Used to respect time settings of [[from()]] and [[to()]] filters.
     * Note: This method has to be called before the [[from()]] and [[to()]] or any other
     * date interval filter in order to affect the query.
     *
     * @param boolean $withTime
     * @return $this
     */
    public function withTime($withTime = true)
    {
        $this->_withTime = $withTime;
        return $this;
    }

    /**
     * Sets the filter array.
     *
     * @param array $filters
     * @return $this
     */
    public function filter($filters = [])
    {
        $this->_filters = $filters;
        return $this;
    }


    /**
     * Filters entries the given user was invited to.
     * @return $this
     */
    public function invited()
    {
        return $this->addFilter(self::FILTER_INVITED);
    }

    /**
     * Filters entries the given user has accepted to.
     * @return $this
     */
    public function participate()
    {
        return $this->addFilter(self::FILTER_PARTICIPATE);
    }

    /**
     * Filters entries the given user has responded (Accept/Decline).
     * @return $this
     */
    public function responded()
    {
        return $this->addFilter(self::FILTER_RESPONDED);
    }

    /**
     * Filters entries the given user has not responded yet.
     * @return $this
     */
    public function notResponded()
    {
        return $this->addFilter(self::FILTER_NOT_RESPONDED);
    }

    /**
     * Filters entries of the given user.
     * @return $this
     */
    public function mine()
    {
        return $this->addFilter(self::FILTER_MINE);
    }

    /**
     * Adds a single filter to the query.
     * @param int $filter
     * @return $this
     */
    public function addFilter($filter)
    {
        if (!in_array($filter, $this->_filters)) {
            $this->_filters[] = $filter;
        }

        return $this;
    }

    /**
     * Filter entries of the given [[ContentContainerActiveRecord]].
     * @param ContentContainerActiveRecord $container
     * @return $this
     */
    public function container(ContentContainerActiveRecord $container = null)
    {
        $this->_container = $container;
        return $this;
    }

    /**
     * Used to deactivate the openRange behaviour, which includes entries if
     * either the start or end date is within the given date filter interval.
     *
     * If this behaviour is deactivated, only entries where the start and end date
     * is within the date filter interval will be included.
     *
     * @param boolean $openRange false to deactivate the openRange behaviour else ture (default)
     * @return $this
     */
    public function openRange($openRange = true)
    {
        $this->_openRange = $openRange;
        return $this;
    }

    /**
     * Sets the query order string.
     *
     * By default `start_datetime ASC`
     *
     * @param string $order sql order string
     * @return $this
     */
    public function orderBy($order)
    {
        $this->_orderBy = $order;
        return $this;
    }

    /**
     * Sets the result limit.
     *
     * @param int $limit
     * @return $this
     */
    public function limit($limit)
    {
        $this->_limit = $limit;
        return $this;
    }

    /**
     * Sets the date filter interval end date.
     *
     * Note: If the [[withTime()]] behaviour is deactivated (default) the time of the
     * end date will be set to 23:59:59 by default.
     *
     * This method accepts either an DateTime instance or an int value:
     *
     * ```php
     * // Set the current date as end date
     * CalendarEntryQuery::find()->to();
     *
     * // Set a specific date as end date
     * CalendarEntryQuery::find()->to($someDate);
     *
     * // Set date three days in the future as end date
     * CalendarEntryQuery::find()->to(3)->all();
     *
     * // Set date three days in the past as end date
     * CalendarEntryQuery::find()->to(-3)->all();
     *
     *  // Set date three month in the future as end date
     * CalendarEntryQuery::find()->to(3, 'M')->all();
     * ```
     * The dateUnit added or substracted to the current date if using an int value can
     * be managed by the second $dateUnit parameter.
     *
     * @param int|DateTime $to specifies the actual end date either by an interval (int) or an actual DateTime instance
     * @return $this
     */
    public function to($to = null, $dateUnit = 'D')
    {
        if (!$to) {
            $to = new DateTime();
        }

        if (is_int($to)) {
            if ($to >= 0) {
                $to = (new DateTime)->add(new DateInterval('P' . $to . $dateUnit));
            } else {
                $to = (new DateTime)->sub(new DateInterval('P' . abs($to) . $dateUnit));
            }
        }

        $this->_to = $to;

        if (!$this->_withTime) {
            $this->_to->setTime(23, 59, 59);
        }

        return $this;
    }

    /**
     * Sets the date filter interval start date.
     *
     * Note: If the [[withTime()]] behaviour is deactivated (default) the time of the
     * start date will be set to 00:00:00 by default.
     *
     * This method accepts either an DateTime instance or an int value:
     *
     * ```php
     * // Set the current date as start date
     * CalendarEntryQuery::find()->from();
     *
     * // Set a specific date as start date
     * CalendarEntryQuery::find()->from($someDate);
     *
     * // Set date three days in the future as start date
     * CalendarEntryQuery::find()->from(3)->all();
     *
     * // Set date three days in the past as start date
     * CalendarEntryQuery::find()->from(-3)->all();
     *
     *  // Set date three month in the future as start date
     * CalendarEntryQuery::find()->from(3, 'M')->all();
     * ```
     * The dateUnit added or substracted to the current date if using an int value can
     * be managed by the second $dateUnit parameter.
     *
     * @param int|DateTime $to specifies the actual end date either by an interval (int) or an actual DateTime instance
     * @return $this
     */
    public function from($from = null, $dateUnit = 'D')
    {
        if (!$from) {
            $from = new DateTime();
        }

        if (is_int($from)) {
            if ($from >= 0) {
                $from = (new DateTime)->add(new DateInterval('P' . $from . $dateUnit));
            } else {
                $from = (new DateTime)->sub(new DateInterval('P' . abs($from) . $dateUnit));
            }
        }

        $this->_from = $from;

        if (!$this->_withTime) {
            $this->_from->setTime(0, 0, 0);
        }

        return $this;
    }

    /**
     * Used to set the date filter interval in days.
     *
     * ```php
     * // Include all entries from $someDate to $someDate + 3 days
     * CalendarEntryQuery::find()->from($someDate)->days(3)->all();
     *
     * // Include all entries from $someDate -3 to $someDate
     * CalendarEntryQuery::find()->to($someDate)->days(-3)->all();
     *
     * // Find all entries from today till 3 days in the future
     * CalendarEntryQuery::find()->days(3)->all();
     *
     * // Find all entries from 3 days in the past until today
     * CalendarEntryQuery::find()->days(-3)->all();
     * ```
     * @param int $days interval either positive or negative
     * @return $this
     * @see interval()
     */
    public function days($days)
    {
        return $this->interval($days);
    }

    /**
     * Used to set the date filter interval in months.
     *
     * ```php
     * // Include all entries from $someDate to $someDate + 3 months
     * CalendarEntryQuery::find()->from($someDate)->months(3)->all();
     *
     * // Include all entries from $someDate -3 months to $someDate
     * CalendarEntryQuery::find()->to($someDate)->months(-3)->all();
     *
     * // Find all entries from today till 3 months in the future
     * CalendarEntryQuery::find()->months(3)->all();
     *
     * // Find all entries from 3 months in the past until today
     * CalendarEntryQuery::find()->months(-3)->all();
     * ```
     * @param int $months interval either positive or negative
     * @return $this
     * @see interval()
     */
    public function months($months)
    {
        return $this->interval($months, 'M');
    }

    /**
     * Used to set the date filter interval in years.
     *
     * ```php
     * // Include all entries from $someDate to $someDate + 3 years
     * CalendarEntryQuery::find()->from($someDate)->years(3)->all();
     *
     * // Include all entries from $someDate -3 to $someDate
     * CalendarEntryQuery::find()->to($someDate)->years(-3)->all();
     *
     * // Find all entries from today till 3 years in the future
     * CalendarEntryQuery::find()->years(3)->all();
     *
     * // Find all entries from 3 years in the past until today
     * CalendarEntryQuery::find()->years(-3)->all();
     * ```
     * @param int $years interval either positive or negative
     * @return $this
     * @see interval()
     */
    public function years($years)
    {
        return $this->interval($years, 'Y');
    }

    /**
     * Used to either add the given $interval to the start date (end date = start date + interval)
     * or substract the given $interval  from the end date (start date = end date - interval).
     *
     * @param integer $dayRange
     * @param string $dateUnit
     * @return $this
     */
    public function interval($interval, $dateUnit = "D")
    {
        if ($interval >= 0) {
            if (!$this->_from) {
                $this->from(); // set from now
            }

            $to = clone $this->_from;
            $to->add(new DateInterval("P" . $interval . $dateUnit));
            $this->to($to);
            return $this;
        } else {
            if (!$this->_to) {
                $this->_to = new DateTime();
            }

            $from = clone $this->_to;
            $from->sub(new DateInterval("P" . abs($interval) . $dateUnit));
            $this->from($from);
            return $this;
        }
    }


    /**
     * Returns the actual \yii\db\ActiveQuery instance.
     * If $build is set to true, this method will build the filter query before.
     *
     * @param bool $build if ture this method will build the filter query before returning
     * @return \yii\db\ActiveQuery
     */
    public function query($build = false)
    {
        if ($build) {
            $this->setupQuery();
        }

        return $this->_query;
    }

    /**
     * Builds and executes the filter query.
     * This method will filter out entries not readable by the current logged in user.
     * @return [] result
     */
    public function all()
    {
        try {
            if (!$this->_built) {
                $this->setupQuery();
            }

            return $this->_query->all();
        } catch(FilterNotSupportedException $e) {
            return [];
        }
    }

    /**
     * Sets up the actual filter query.
     */
    protected function setupQuery()
    {
        $this->setupCriteria();
        $this->setupFilters();
        $this->_built = true;
    }

    /**
     * Sets up the non _filter array related queries.
     */
    protected function setupCriteria()
    {
        if ($this->_container) {
            $this->filterContentContainer();
        }

        if($this->hasFilter(self::FILTER_USERRELATED)) {
            $this->_userScopes = $this->_filters[self::FILTER_USERRELATED];
        }

        if (!empty($this->_userScopes)) {
            $this->filterUserRelated();
        }

        $this->setupDateCriteria();

        if(!$this->_orderBy) {
            $this->_query->orderBy($this->startField.' ASC');
        } else {
            $this->_query->orderBy($this->_orderBy);
        }

        if ($this->_limit) {
            $this->_query->limit($this->_limit);
        }

        $this->filterReadable();
    }

    /**
     * Sets up the date interval filter with respect to the openRange setting.
     */
    protected function setupDateCriteria()
    {
        if ($this->_openRange && $this->_from && $this->_to) {
            //Search for all dates with start and/or end within the given range
            $this->_query->andFilterWhere(
                ['or',
                    ['and',
                        $this->getStartCriteria($this->_from, '>='),
                        $this->getStartCriteria($this->_to, '<=')
                    ],
                    ['and',
                        $this->getEndCriteria($this->_from, '>='),
                        $this->getEndCriteria($this->_to, '<=')
                    ]
                ]
            );
            return;
        }

        if ($this->_from) {
            $this->_query->andWhere($this->getStartCriteria($this->_from));
        }

        if ($this->_to) {
            $this->_query->andWhere($this->getEndCriteria($this->_to));
        }
    }

    /**
     * Helper function to get the start_datetime query filter.
     * @param DateTime $date
     * @param string $eq
     * @return array
     */
    protected function getStartCriteria(DateTime $date, $eq = '>=')
    {
        return [$eq, $this->startField, $date->format($this->dateFormat)];
    }

    /**
     * Helper function to get the end_datetime query filter.
     * @param DateTime $date
     * @param string $eq
     * @return array
     */
    protected function getEndCriteria(DateTime $date, $eq = '<=')
    {
        return [$eq, $this->endField, $date->format($this->dateFormat)];
    }

    /**
     * Sets up the filters contained in the $_filter array.
     */
    protected function setupFilters()
    {
        if (empty($this->_filters)) {
            return;
        }

        if ($this->hasFilter(self::FILTER_PARTICIPATE)) {
            $this->filterIsParticipant();
        }

        if ($this->hasFilter(self::FILTER_INVITED)) {
            $this->filterIsInvited();
        }

        if ($this->hasFilter(self::FILTER_RESPONDED)) {
            $this->filterResponded();
        }
        if ($this->hasFilter(self::FILTER_NOT_RESPONDED)) {
            $this->filterNotResponded();
        }

        if ($this->hasFilter(self::FILTER_MINE)) {
            $this->filterMine();
        }
    }

    protected function hasFilter($filter) {
        return in_array($filter, $this->_filters) || array_key_exists($filter, $this->_filters);
    }

    protected function filterReadable()
    {
        if($this->_query instanceof ActiveQueryContent) {
            $this->_query->readable();
        }
    }

    protected function filterContentContainer()
    {
        if($this->_query instanceof ActiveQueryContent) {
            $this->_query->contentContainer($this->_container);
        } else {
            throw new FilterNotSupportedException('Contentcontainer filter not supported for this query');
        }
    }

    protected function filterUserRelated()
    {
        if($this->_query instanceof ActiveQueryContent) {
            $this->_query->userRelated($this->_userScopes);
        } else {
            throw new FilterNotSupportedException('User related filter not supported for this query');
        }
    }

    public function filterMine()
    {
        if($this->_query instanceof ActiveQueryContent) {
            $this->_query->andWhere(['content.created_by' => $this->_user->contentcontainer_id]);
        } else {
            throw new FilterNotSupportedException('Mine filter not supported for this query');
        }
    }

    public function filterResponded()
    {
        throw new FilterNotSupportedException('Responded filter not supported for this query');
    }

    public function filterNotResponded()
    {
        throw new FilterNotSupportedException('Not Responded filter not supported for this query');
    }

    public function filterIsParticipant()
    {
        throw new FilterNotSupportedException('Participant filter not supported for this query');
    }

    protected function filterIsInvited()
    {
        throw new FilterNotSupportedException('Invited filter not supported for this query');
    }
}