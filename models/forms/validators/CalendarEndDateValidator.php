<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\calendar\models\forms\validators;

use Yii;
use yii\base\Model;
use yii\validators\Validator;

/**
 * Validates the end date which should be bigger thant start date
 * 
 * @package humhub\modules\calendar\models\forms\validators
 */
class CalendarEndDateValidator extends Validator
{
    /**
     * @param Model $model
     * @param string $attribute
     */
    public function validateAttribute($model, $attribute)
    {
        if ($model->getStartDateTime() >= $model->getEndDateTime()) {
            $this->addError($model, $attribute, Yii::t('CalendarModule.base', "End time must be after start time!"));
        }
    }
}