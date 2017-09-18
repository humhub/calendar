<?php

namespace humhub\modules\calendar\widgets;

use humhub\modules\calendar\assets\Assets;
use humhub\modules\calendar\permissions\ManageEntry;
use humhub\modules\file\widgets\ShowFiles;
use Solarium\QueryType\Update\Query\Command\Delete;
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
     * @var bool defines if the description and participation info should be cut at a certain height, this should only be the case in the stream
     */
    public $stream = true;

    /**
     * @var bool defines if the content should be collapsed
     */
    public $collapse = true;

    /**
     * @inheritdoc
     */
    public $addonOptions = [
        ShowFiles::class => [
            'preview' => false
        ]
    ];

    public function getContextMenu()
    {
        $canEdit = $this->contentObject->content->canEdit();
        if($canEdit) {
            $this->controlsOptions = [
                'add' => [
                    [CloseLink::class, ['entry' => $this->contentObject], ['sortOrder' => 210]]
                ]
            ];
        }

        if($this->stream) {
            return parent::getContextMenu();
        }

        $this->controlsOptions['prevent'] = [\humhub\modules\content\widgets\EditLink::class , \humhub\modules\content\widgets\DeleteLink::class];
        $result = parent::getContextMenu();

        if($canEdit) {
            $this->addControl($result, [DeleteLink::class, ['entry' => $this->contentObject], ['sortOrder' => 100]]);
            $this->addControl($result, [EditLink::class, ['entry' => $this->contentObject], ['sortOrder' => 200]]);
        }

        return $result;
    }

    public function getWallEntryViewParams()
    {
        $params = parent::getWallEntryViewParams();
        if($this->isInModal()) {
            $params['showContentContainer'] = true;
        }
        return $params;
    }

    public function isInModal()
    {
        return Yii::$app->request->get('cal');
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        Assets::register($this->getView());
        $entry = $this->contentObject;

        return $this->render('wallEntry', [
            'calendarEntry' => $entry,
            'collapse' => $this->collapse,
            'participantSate' => $entry->getParticipationState(),
            'contentContainer' => $entry->content->container
        ]);
    }
}

?>