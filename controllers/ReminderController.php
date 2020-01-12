<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\controllers;

use Yii;
use yii\web\HttpException;
use humhub\components\access\ControllerAccess;
use humhub\modules\calendar\helpers\CalendarUtils;
use humhub\modules\calendar\interfaces\reminder\CalendarEventReminderIF;
use humhub\modules\calendar\models\reminder\forms\ReminderSettings;
use humhub\modules\content\components\ContentContainerController;
use humhub\modules\content\models\Content;
use humhub\widgets\ModalClose;

class ReminderController extends ContentContainerController
{
    /**
     * @return array
     */
    public function getAccessRules()
    {
        return [
            [ControllerAccess::RULE_LOGGED_IN_ONLY]
        ];
    }

    /**
     * @param $id
     * @return string
     * @throws HttpException
     * @throws \Throwable
     * @throws \yii\base\Exception
     */
    public function actionSet($id)
    {
        $content = Content::findOne(['id' => $id]);

        if(!$content) {
            throw new HttpException(404);
        }

        if(!$content->canView()) {
            throw new HttpException(403);
        }

        $model = CalendarUtils::getCalendarEvent($content);

        if(!$model || !($model instanceof CalendarEventReminderIF)) {
            throw new HttpException(400);
        }

        $reminderSettings = new ReminderSettings(['entry' =>$model, 'user' => Yii::$app->user->getIdentity()]);

        if($reminderSettings->load(Yii::$app->request->post()) && $reminderSettings->save()) {
            return ModalClose::widget(['saved' => true]);
        }

        return $this->renderAjax('userLevelReminder', ['reminderSettings' => $reminderSettings]);
    }
}