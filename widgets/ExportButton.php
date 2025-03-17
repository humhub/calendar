<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2025 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\widgets;

use humhub\components\Widget;
use humhub\modules\calendar\helpers\Url;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\components\ContentContainerModuleManager;
use humhub\modules\content\helpers\ContentContainerHelper;
use humhub\modules\content\models\ContentContainerModuleState;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use humhub\widgets\Button;
use humhub\widgets\ModalButton;
use Yii;

class ExportButton extends Widget
{
    /**
     * @var ContentContainerActiveRecord
     */
    public $container;

    public function run()
    {
        if(Yii::$app->user->isGuest) {
            return;
        }

        return ModalButton::defaultType()
            ->icon('download')
            ->load(Url::to(['/calendar/config/export']))
            ->tooltip(Yii::t('CalendarModule.views', 'Export'));
    }
}
