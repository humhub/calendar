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
            'options' => $this->getOptions(),
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function getData()
    {
        return [
            'entry-id' => $this->entry->id,
            'add-url' => $this->entry->content->container->createUrl('/calendar/entry/add-participants'),
            'update-url' => $this->entry->content->container->createUrl('/calendar/entry/update-participant-status'),
            'remove-url' => $this->entry->content->container->createUrl('/calendar/entry/remove-participant'),
        ];
    }
}
