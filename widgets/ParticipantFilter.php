<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\widgets;

use humhub\components\Widget;
use Yii;

/**
 * ParticipantFilter widget to filter participants list
 */
class ParticipantFilter extends Widget
{
    /**
     * @var string
     */
    public $state;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->state === null) {
            $this->state = Yii::$app->request->get('state', Yii::$app->request->post('state', ''));
        }
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        return $this->render('participantFilter', [
            'state' => $this->state,
            'statuses' => self::getStatuses(),
        ]);
    }

    public static function getStatuses(): array
    {
        return ['' => Yii::t('CalendarModule.views', 'All')]
            + ParticipantItem::getStatuses();
    }
}
