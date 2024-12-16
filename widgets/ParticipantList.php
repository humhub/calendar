<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\widgets;

use humhub\components\Widget;
use humhub\modules\calendar\models\forms\CalendarEntryParticipationForm;
use humhub\modules\ui\form\widgets\ActiveForm;
use humhub\modules\user\models\User;
use Yii;
use yii\data\Pagination;

/**
 * ParticipantList to display all participants of the Calendar entry
 */
class ParticipantList extends Widget
{
    /**
     * @var ActiveForm
     */
    public $form;

    /**
     * @var CalendarEntryParticipationForm
     */
    public $model;

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
        $usersQuery->where(['calendar_entry_id' => $this->model->entry->id]);
        $state = Yii::$app->request->get('state', Yii::$app->request->post('state', ''));
        if (!empty($state) && ParticipantItem::hasStatus($state)) {
            $usersQuery->andWhere(['participation_state' => $state]);
        }

        $countQuery = clone $usersQuery;
        $pagination = new Pagination([
            'totalCount' => $countQuery->count(),
            'pageSize' => $this->pageSize,
            'route' => '/calendar/entry/participants-list',
        ]);
        $usersQuery->offset($pagination->offset)->limit($pagination->limit);

        return $this->render('participantList', [
            'form' => $this->form,
            'model' => $this->model,
            'users' => $usersQuery->all(),
            'pagination' => $pagination,
        ]);
    }
}
