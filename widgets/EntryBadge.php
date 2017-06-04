<?php

namespace humhub\modules\calendar\widgets;

use Yii;
use yii\helpers\Html;
use humhub\components\Widget;
use humhub\modules\calendar\models\CalendarEntryParticipant;

/**
 * Description of EntryBadge
 *
 * @author buddha
 */
class EntryBadge extends Widget
{
    /**
     * @var \humhub\modules\calendar\models\CalendarEntry 
     */
    public $entry;

     /**
     * @inerhitdoc
     */
    public function run()
    {
        $participant = $this->entry->findParticipant();
        
        $result = '';
        if($participant) {
            switch($participant->participation_state) {
                case CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED:
                    return Html::tag('span', Yii::t('CalendarModule.base', 'Attending'), ['class' => 'label label-success pull-right']);
                case CalendarEntryParticipant::PARTICIPATION_STATE_INVITED:
                    return Html::tag('span', Yii::t('CalendarModule.base', 'Invited'), ['class' => 'label label-info pull-right']);
                case CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE:
                    return Html::tag('span', Yii::t('CalendarModule.base', 'Interested'), ['class' => 'label label-info pull-right']);
            }
        }
    }

}
