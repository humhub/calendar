<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace humhub\modules\calendar\models\forms\validators;

use humhub\modules\calendar\models\forms\CalendarEntryForm;
use Yii;
use humhub\modules\calendar\models\CalendarEntryType;
use yii\validators\Validator;

/**
 * Validates a date time field
 * 
 * @package humhub\modules\calendar\models\forms\validators
 */
class CalendarTypeValidator extends Validator
{
    /**
     * @param CalendarEntryForm $model
     * @param string $attribute
     */
    public function validateAttribute($model, $attribute)
    {
        if ($model->type_id === null) {
            return;
        }

        $type = CalendarEntryType::findOne(['id' => $model->type_id]);

        if (!empty($type->contentcontainer_id) && $type->contentcontainer_id !== $model->entry->content->contentcontainer_id) {
            $this->addError($model, $attribute, Yii::t('CalendarModule.base', "Invalid event type id selected."));
        }
    }
}