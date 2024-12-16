<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\widgets;

use humhub\components\Widget;
use humhub\modules\calendar\helpers\Url;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\widgets\Button;
use Yii;

/**
 * Class ExportParticipantsButton
 */
class ExportParticipantsButton extends Widget
{
    public CalendarEntry $entry;

    public ?string $state = null;

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
    public function beforeRun()
    {
        return parent::beforeRun() && $this->entry->content->canEdit();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        echo $this->render('exportParticipantsButton', [
            'buttons' => $this->getButtons(),
        ]);
    }

    /**
     * @return Button[]
     */
    private function getButtons(): array
    {
        return [
            Button::none(Yii::t('CalendarModule.base', 'Export as {type}', ['type' => 'csv']))
                ->link(Url::toExportParticipations('csv', $this->entry, $this->state))
                ->icon('file-code-o'),
            Button::none(Yii::t('CalendarModule.base', 'Export as {type}', ['type' => 'xlsx']))
                ->link(Url::toExportParticipations('xlsx', $this->entry, $this->state))
                ->icon('file-excel-o'),
        ];
    }
}
