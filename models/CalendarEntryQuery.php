<?php
namespace humhub\modules\calendar\models;

use humhub\modules\space\models\Space;
use Yii;
use DateTime;
use DateInterval;
use humhub\modules\user\models\User;
use humhub\modules\content\components\ActiveQueryContent;
use humhub\modules\content\components\ContentContainerActiveRecord;

/**
 * CalendarEntryQuery class can be used for creating filter queries for [[CalendarEntry]] models.
 * 
 * The class follows the builder pattern and can be used as follows:
 * 
 *  ```php
 * // Find all CalendarEntries of user profile of $user1 
 * CalendarEntryQuery::find()->container($user1)->limit(20)->all();
 * 
 * // Find all entries from 3 days in the past till three days in the future
 * CalendarEntryQuery::find()->from(-3)->to(3)->all();
 * 
 * // Find all entries within today at 00:00 till three days in the future at 23:59
 * CalendarEntryQuery::find()->days(3)->all();
 * 
 * // Filter entries where the current user is participating
 * CalendarEntryQuery::find()->participate();
 * 
 * // Filter entries where $user1 is invited
 * CalendarEntryQuery::find($user1)->invited()->all();
 * 
 * // Only build the query of the last example
 * $query = CalendarEntryQuery::find($user1)->invited()->query(true);
 * ```
 * 
 * > Note: If [[from()]] and [[to()]] is set, the query will use an open range query by default, which
 * means either the start time or the end time of the [[CalendarEntry]] has to be within the searched interval.
 * This behaviour can be changed by using the [[openRange()]]-method. If the openRange behaviour is deactivated
 * only entries with start and end time within the search interval will be included.
 * 
 * > Note: By default we are searching in whole day intervals and get rid of the time information of from/to boundaries by setting
 * the time of the from date to 00:00:00 and the time of the end date to 23:59:59. This behaviour can be deactivated by using the [[withTime()]]-method.
 * 
 * The following filters are available:
 * 
 *  - [[from()]]: Date filter interval start
 *  - [[to()]]: Date filter interval end
 *  - [[days()]]: Filter by future or past day interval
 *  - [[months()]]: Filter by future or past month interval
 *  - [[years()]]: Filter by future or past year interval
 * 
 *  - [[container()]]: Filter by container
 *  - [[userRelated()]]: Adds a user relation by the given or default scope (e.g: Following Spaces, Member Spaces, Own Profile, etc.)
 *  - [[invited()]]: Given user is invited
 *  - [[participant()]]: Given user accepted invitation
 *  - [[mine()]]: Entries created by the given user
 *  - [[responded()]]: Entries where given user has given any response (accepted/declined...)
 *  - [[notResponded()]]: Entries where given user has not given any response yet (accepted/declined...)
 *
 * @author buddha
 */
class CalendarEntryQuery extends \yii\base\Model
{

    /**
     * Available filters
     */
    const FILTER_PARTICIPATE = 1;
    const FILTER_INVITED = 2;
    const FILTER_NOT_RESPONDED = 3;
    const FILTER_RESPONDED = 4;
    const FILTER_MINE = 5;

    /**
     * @var array Activated query filters
     */
    protected $_filters = [];

    /**
     * @var \yii\db\ActiveQuery the actual query instance
     */
    private $_query;

    /**
     * @var DateTime start date of the filter interval
     */
    private $_from;

    /**
     * @var DateTime end date of the filter interval
     */
    private $_to;

    /**
     * @var boolean flag to enable/disable the openRange behaviour (default true) 
     */
    private $_openRange = true;

    /**
     * @var string query order string
     */
    private $_orderBy = 'start_datetime ASC';

    /**
     * @var int query limit 
     */
    private $_limit = 0;

    /**
     * @var \humhub\modules\content\components\ContentContainerActiveRecord used to filter entries by contentContainer
     */
    private $_container;

    /**
     * @var \humhub\modules\user\models\User user instance used for some of the filters e.g. mine() filter 
     */
    private $_user;

    /**
     * @var array user related scopes used for [[userRelated()]] filters
     */
    private $_userScopes;

    /**
     * @var boolean if set to false (default) will ignore time information in date filter intervals 
     */
    private $_withTime = false;

    /**
     * @var boolean determines if the query was already built
     */
    protected $_built = false;

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

        $instance = new self();
        $instance->_query = CalendarEntry::find();
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
     * @param type $withTime
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
     * @param int[] $filter
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
    public function container(ContentContainerActiveRecord $container)
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
     * @param type $dayRange
     * @param type $dateUnit
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
     * @param type $build if ture this method will build the filter query before returning
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
     * @return CalendarEntry[] result
     */
    public function all()
    {
        if (!$this->_built) {
            $this->setupQuery();
        }

        return $this->_query->all();
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
            $this->_query->contentContainer($this->_container);
        }

        if (!empty($this->_userScopes)) {
            $this->_query->userRelated($this->_userScopes);
        }

        $this->setupDateCriteria();

        if ($this->_orderBy) {
            $this->_query->orderBy($this->_orderBy);
        }

        if ($this->_limit) {
            $this->_query->limit($this->_limit);
        }

        // Patches a guest mode related bug in ActiveQueryContent
        if(!$this->_user && version_compare(Yii::$app->version, '1.2.1', 'lt')) {
            $this->_query->leftJoin('space sp', 'contentcontainer.pk=sp.id AND contentcontainer.class=:spaceClass', [':spaceClass' => Space::className()]);
        }

        $this->_query->readable();
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
     * @return type
     */
    private function getStartCriteria(DateTime $date, $eq = '>=')
    {
        return [$eq, 'start_datetime', $date->format('Y-m-d H:i:s')];
    }

    /**
     * Helper function to get the end_datetime query filter.
     * @param DateTime $date
     * @param string $eq
     * @return type
     */
    private function getEndCriteria(DateTime $date, $eq = '<=')
    {
        return [$eq, 'end_datetime', $date->format('Y-m-d H:i:s')];
    }

    /**
     * Sets up the filters contained in the $_filter array.
     */
    protected function setupFilters()
    {
        if (empty($this->_filters)) {
            return;
        }

        $this->_query->leftJoin('calendar_entry_participant', 'calendar_entry.id=calendar_entry_participant.calendar_entry_id AND calendar_entry_participant.user_id=:userId', [':userId' => $this->_user->id]);

        if (in_array(self::FILTER_PARTICIPATE, $this->_filters)) {
            $this->_query->andWhere(['calendar_entry_participant.participation_state' => CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED]);
        }
        if (in_array(self::FILTER_INVITED, $this->_filters)) {
            $this->_query->andWhere(['calendar_entry_participant.participation_state' => CalendarEntryParticipant::PARTICIPATION_STATE_INVITED]);
        }
        if (in_array(self::FILTER_RESPONDED, $this->_filters)) {
            $this->_query->andWhere(['IS NOT', 'calendar_entry_participant.id', new \yii\db\Expression('NULL')]);
        }
        if (in_array(self::FILTER_NOT_RESPONDED, $this->_filters)) {
            $this->_query->andWhere(['IS', 'calendar_entry_participant.id', new \yii\db\Expression('NULL')]);
        }
        if (in_array(self::FILTER_MINE, $this->_filters)) {
            $this->_query->andWhere(['content.created_by' => $this->_user->contentcontainer_id]);
        }
    }
}
