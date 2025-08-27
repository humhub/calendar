<?php

namespace humhub\modules\calendar\widgets;

use humhub\modules\calendar\assets\CalendarBaseAssets;
use humhub\modules\calendar\helpers\RecurrenceHelper;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\permissions\ManageEntry;
use humhub\modules\content\widgets\stream\WallStreamModuleEntryWidget;
use Yii;

class WallEntry extends WallStreamModuleEntryWidget
{
    /**
     * @inheritdoc
     */
    public $createRoute = '/calendar/entry/add-from-wall';

    public const VIEW_CONTEXT_FULLCALENDAR = 'fullCalendar';

    /**
     * @var CalendarEntry
     */
    public $model;

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
     * @return array
     * @throws \Throwable
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\IntegrityException
     */
    public function getControlsMenuEntries()
    {
        $result = parent::getControlsMenuEntries();

        // Moving currently not supported for recurrent events
        if (RecurrenceHelper::isRecurrent($this->model)) {
            $this->renderOptions->disableControlsEntryMove();
        }

        if ($this->model->content->canEdit()) {
            $result[] = [CloseLink::class, ['entry' => $this->model], ['sortOrder' => 210]];

            // We need special edit/delete behavior in full calendar view
            if ($this->renderOptions->isViewContext(static::VIEW_CONTEXT_FULLCALENDAR)) {
                $this->renderOptions->disableControlsEntryEdit()->disableControlsEntryDelete();
                $result[] = [EditLink::class, ['entry' => $this->model], ['sortOrder' => 100]];
                $result[] = [DeleteLink::class, ['entry' => $this->model], ['sortOrder' => 200]];
            }
        }

        $result[] = [ParticipantsLink::class, ['entry' => $this->model], ['sortOrder' => 110]];

        return $result;
    }

    public function getWallEntryViewParams()
    {
        $params = parent::getWallEntryViewParams();
        if ($this->isInModal()) {
            $params['showContentContainer'] = true;
        }
        return $params;
    }

    public function isInModal()
    {
        return Yii::$app->request->get('cal');
    }

    /**
     * @return string returns the content type specific part of this wall entry (e.g. post content)
     */
    protected function renderContent()
    {
        CalendarBaseAssets::register($this->getView());
        /* @var $entry CalendarEntry */
        $entry = $this->model;

        return $this->render('wallEntry', [
            'calendarEntry' => $entry,
            'collapse' => $this->collapse,
            'participantSate' => $entry->getParticipationStatus(Yii::$app->user->identity),
            'contentContainer' => $entry->content->container,
        ]);
    }

    /**
     * @return string a non encoded plain text title (no html allowed) used in the header of the widget
     */
    protected function getTitle()
    {
        return $this->model->title;
    }
}
