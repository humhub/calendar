<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\widgets;

use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\user\models\User;
use Yii;
use yii\base\Widget;
use yii\data\Pagination;

/**
 * ParticipantList to display all participants of the Calendar entry
 */
class ParticipantList extends Widget
{

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
        $usersQuery = User::find();
        $usersQuery->innerJoin('calendar_entry_participant', 'user.id = user_id');
        $usersQuery->where(['calendar_entry_id' => $this->entry->id]);

        $countQuery = clone $usersQuery;
        $pagination = new Pagination(['totalCount' => $countQuery->count(), 'pageSize' => $this->pageSize]);
        $usersQuery->offset($pagination->offset)->limit($pagination->limit);

        return $this->render('participantList', [
            'entry' => $this->entry,
            'users' => $usersQuery->all(),
            'pagination' => $pagination,
        ]);
    }

}
