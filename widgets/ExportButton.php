<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2025 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\widgets;

use humhub\components\Widget;
use humhub\modules\calendar\helpers\ICalHelper;
use humhub\modules\calendar\helpers\Url;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\helpers\ContentContainerHelper;
use humhub\widgets\ModalButton;
use Yii;

class ExportButton extends Widget
{
    /**
     * @var ContentContainerActiveRecord
     */
    public $container;

    public function init()
    {
        $this->container = ContentContainerHelper::getCurrent();

        if (!$this->container) {
            $this->container = Yii::$app->user->identity;
        }

        parent::init();
    }

    public function run()
    {
        if(Yii::$app->user->isGuest) {
            return;
        }

        return ModalButton::defaultType()
            ->icon('download')
            ->load(Url::to(['/calendar/global/export', 'guid' => $this->container->contentContainerRecord->guid]))
            ->tooltip(Yii::t('CalendarModule.views', 'Export'));
    }
}
