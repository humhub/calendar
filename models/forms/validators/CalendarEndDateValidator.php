<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\calendar\models\forms\validators;

use Yii;
use yii\validators\Validator;
use humhub\modules\calendar\models\forms\CalendarEntryForm;

/**
 * Validates the end date which should be bigger thant start date
 * 
 * @package humhub\modules\calendar\models\forms\validators
 */
class CalendarEndDateValidator extends Validator
{
    /**
     * @param CalendarEntryForm $model
     * @param string $attribute
     */
    public function validateAttribute($model, $attribute)
    {
        $valid = $model->isAllDay()
            ? $model->getStartDateTime() <= $model->getEndDateTime()
            : $model->getStartDateTime() < $model->getEndDateTime();

        if (!$valid) {
            $this->addError($model, $attribute, Yii::t('CalendarModule.base', "End time must be after start time!"));
        }
    }
}