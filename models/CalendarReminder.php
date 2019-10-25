<?php


namespace humhub\modules\calendar\models;


use humhub\components\ActiveRecord;
use humhub\components\behaviors\PolymorphicRelation;
use humhub\modules\content\components\ContentContainerActiveRecord;
use yii\db\Expression;

/**
 * Class CalendarReminder
 * @package humhub\modules\calendar\models
 *
 * @property integer id
 * @property string value
 * @property integer unit
 * @property string object_model
 * @property integer object_id
 * @property integer sent
 * @property integer contentcontainer_id
 * @property integer active
 */
class CalendarReminder extends ActiveRecord
{
    const UNIT_HOUR = 1;
    const UNIT_DAY = 2;
    const UNIT_WEEK = 3;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'calendar_reminder';
    }

    public function rules()
    {
        $rules = [
            [['unit'], 'in', 'range' => [static::UNIT_HOUR, static::UNIT_DAY, static::UNIT_WEEK]],
            [['value'], 'integer', 'min' => 1, 'max' => '30']
        ];

        if($this->active) {
            $rules[] = [['unit', 'value'], 'required'];
        }

        return $rules;
    }

    public function behaviors()
    {
        return [
            [
                'class' => PolymorphicRelation::class,
                'mustBeInstanceOf' => [ContentContainerActiveRecord::class]
            ]
        ];
    }

    /**
     * @param ContentContainerActiveRecord|null $container
     * @return \yii\db\ActiveQuery
     */
    public static function getDefaults(ContentContainerActiveRecord $container = null, $globalFallback = false)
    {
        $query = static::find();

        if($container) {
            $query->andWhere(['contentcontainer_id' => $container->contentcontainer_id]);
        } else {
            $query->andWhere(['IS', 'contentcontainer_id', new Expression('NULL')]);
        }

        $result = $query->all();

        if(empty($result) && $container && $globalFallback) {
            return static::getDefaults();
        }

        return $result;
    }

    /**
     * @param ContentContainerActiveRecord|null $container
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public static function clearDefaults(ContentContainerActiveRecord $container = null)
    {
        foreach (static::getDefaults($container)->all() as $reminder) {
            $reminder->delete();
        }
    }

}