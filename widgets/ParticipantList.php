<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\widgets;

use humhub\modules\calendar\assets\ParticipantsListAssets;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\user\models\User;
use humhub\widgets\JsWidget;
use Yii;
use yii\data\Pagination;

/**
 * ParticipantList to display all participants of the Calendar entry
 */
class ParticipantList extends JsWidget
{
    /**
     * @inheritdoc
     */
    public $jsWidget = 'calendar.participants.List';

    /**
     * @inheritdoc
     */
    public $init = true;

    /**
     * @var CalendarEntry
     */
    public $entry;

    /**
     * @var int displayed users per page
     */
    public $pageSize = null;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->pageSize === null) {
            $this->pageSize = Yii::$app->getModule('user')->userListPaginationSize;
        }

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        ParticipantsListAssets::register($this->getView());

        $usersQuery = User::find();
        $usersQuery->innerJoin('calendar_entry_participant', 'user.id = user_id');
        $usersQuery->where(['calendar_entry_id' => $this->entry->id]);

        $countQuery = clone $usersQuery;
        $pagination = new Pagination([
            'totalCount' => $countQuery->count(),
            'pageSize' => $this->pageSize,
            'route' => '/calendar/entry/participants',
        ]);
        $usersQuery->offset($pagination->offset)->limit($pagination->limit);

        return $this->render('participantList', [
            'entry' => $this->entry,
            'users' => $usersQuery->all(),
            'pagination' => $pagination,
            'statuses' => self::getStatuses(),
            'options' => $this->getOptions(),
        ]);
    }

    public static function getStatuses(): array
    {
        return [
            CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED => Yii::t('CalendarModule.views_entry_edit', 'Attend'),
            CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE => Yii::t('CalendarModule.views_entry_edit', 'Maybe'),
            CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED => Yii::t('CalendarModule.views_entry_edit', 'Declined'),
            CalendarEntryParticipant::PARTICIPATION_STATE_INVITED => Yii::t('CalendarModule.views_entry_edit', 'Invited'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getData()
    {
        return [
            'entry-id' => $this->entry->id,
            'update-url' => $this->entry->content->container->createUrl('/calendar/entry/update-participant-status'),
            'remove-url' => $this->entry->content->container->createUrl('/calendar/entry/remove-participant'),
        ];
    }
}
