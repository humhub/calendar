<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\widgets;

use humhub\modules\calendar\assets\ParticipantsListAssets;
use humhub\modules\calendar\models\CalendarEntry;
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
     * @var string 'add', 'invite' - what form should be initalized on load the list
     */
    public $initForm;

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
        $state = Yii::$app->request->get('state', Yii::$app->request->post('state', ''));
        if (!empty($state) && ParticipantItem::hasStatus($state)) {
            $usersQuery->andWhere(['participation_state' => $state]);
        }

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
            'options' => $this->getOptions(),
            'initAddForm' => strpos($this->initForm, 'add') !== false,
            'initInviteForm' => strpos($this->initForm, 'invite') !== false,
        ]);
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
            'filter-url' => $this->entry->content->container->createUrl('/calendar/entry/participants'),
        ];
    }
}
