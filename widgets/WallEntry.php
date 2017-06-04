<?php

namespace humhub\modules\calendar\widgets;

use humhub\modules\calendar\assets\Assets;
use humhub\modules\calendar\permissions\ManageEntry;
use Yii;
use humhub\modules\calendar\models\CalendarEntryParticipant;

class WallEntry extends \humhub\modules\content\widgets\WallEntry
{
    /**
     * @var string
     */
    public $managePermission = ManageEntry::class;

    /**
     * @inheritdoc
     */
    public $editRoute = "/calendar/entry/edit";
    
    /**
     * @inheritdoc
     */
    public $editMode = self::EDIT_MODE_MODAL;
    
    /**
     * @inheritdoc
     */
    public function run()
    {
        Assets::register($this->getView());
        $entry = $this->contentObject;

        return $this->render('wallEntry', [
            'calendarEntry' => $entry,
            'participantSate' => $entry->getParticipationState(),
            'contentContainer' => $entry->content->container
        ]);
    }
}

?>