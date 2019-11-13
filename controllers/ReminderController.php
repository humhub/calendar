<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\controllers;


use humhub\components\access\ControllerAccess;
use humhub\modules\calendar\interfaces\CalendarEventReminderIF;
use humhub\modules\calendar\models\forms\ReminderSettings;
use humhub\modules\content\components\ContentContainerController;
use humhub\modules\content\models\Content;
use humhub\widgets\ModalClose;
use Yii;
use yii\web\HttpException;

class ReminderController extends ContentContainerController
{
    /**
     * @return array
     */
    public function getAccessRules()
    {
        return [[ControllerAccess::RULE_LOGGED_IN_ONLY]];
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

        if(!($content->getModel() instanceof CalendarEventReminderIF)) {
            throw new HttpException(400);
        }

        $reminderSettings = new ReminderSettings(['entry' => $content->getModel(), 'user' => Yii::$app->user->getIdentity()]);

        if($reminderSettings->load(Yii::$app->request->post()) && $reminderSettings->save()) {
            return ModalClose::widget(['saved' => true]);
        }

        return $this->renderAjax('userLevelReminder', ['reminderSettings' => $reminderSettings]);
    }
}