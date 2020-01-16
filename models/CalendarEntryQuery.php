<?php
namespace humhub\modules\calendar\models;

use humhub\modules\calendar\interfaces\AbstractCalendarQuery;
use humhub\modules\calendar\interfaces\recurrence\AbstractRecurrenceQuery;
use humhub\modules\content\components\ContentContainerActiveRecord;
use DateTime;

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
 *  - [[participant()]]: Given user accepted invitation
 *  - [[mine()]]: Entries created by the given user
 *  - [[responded()]]: Entries where given user has given any response (accepted/declined...)
 *  - [[notResponded()]]: Entries where given user has not given any response yet (accepted/declined...)
 *
 * @author buddha
 */
class CalendarEntryQuery extends AbstractRecurrenceQuery
{
    /**
     * @inheritdocs
     */
    protected static $recordClass = CalendarEntry::class;

    /**
     * @inheritdocs
     */
    protected $dateQueryType = self::DATE_QUERY_TYPE_MIXED;

    /**
     * @var bool true if the participant join has already been added else false
     */
    private $praticipantJoined = false;

    public static function findForFilter(DateTime $start = null, DateTime $end = null, ContentContainerActiveRecord $container = null, $filters = [], $limit = 50, $expand = true)
    {
        /* @var $event CalendarEntry */
        $events = parent::findForFilter($start, $end, $container, $filters, $limit, $expand);
        return $events;
    }

    public function filterResponded()
    {
        $this->participantJoin();
        $this->_query->andWhere(['IS NOT', 'calendar_entry_participant.id', new \yii\db\Expression('NULL')]);
    }

    public function filterNotResponded()
    {
        $this->participantJoin();
        $this->_query->andWhere(['IS', 'calendar_entry_participant.id', new \yii\db\Expression('NULL')]);
    }

    public function filterIsParticipant()
    {
        $this->participantJoin();
        $this->_query->andWhere(['calendar_entry_participant.participation_state' => CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED]);
    }

    private function participantJoin()
    {
        if(!$this->praticipantJoined) {
            $this->_query->leftJoin('calendar_entry_participant', 'calendar_entry.id=calendar_entry_participant.calendar_entry_id AND calendar_entry_participant.user_id=:userId', [':userId' => $this->_user->id]);
            $this->praticipantJoined = true;
        }
    }


}
