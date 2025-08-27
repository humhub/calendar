<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\models;

use humhub\modules\calendar\Module;
use Yii;
use yii\base\Model;

class MenuSettings extends Model
{
    /**
     * @var bool
     */
    public $show = true;

    /**
     * @var int
     */
    public $sortOrder = 300;

    /**
     * @var Module
     */
    public $module;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->module = Yii::$app->getModule('calendar');

        $this->show = $this->module->settings->get('menuShow', $this->show);
        $this->sortOrder = $this->module->settings->get('menuSortOrder', $this->sortOrder);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['show', 'boolean'],
            ['sortOrder', 'number', 'min' => 0],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'show' => Yii::t('CalendarModule.config', 'Add \'Calendar\' to the main menu'),
        ];
    }

    /**
     * @return bool
     */
    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $this->module->settings->set('menuShow', $this->show);
        $this->module->settings->set('menuSortOrder', $this->sortOrder);

        return true;
    }
}
