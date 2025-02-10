<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\extensions\custom_pages\elements;

use humhub\libs\Html;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\custom_pages\modules\template\elements\BaseElementContent;
use Yii;

/**
 * Class to manage content records of the Calendar event
 *
 * Dynamic attributes:
 * @property string $id
 */
class CalendarElement extends BaseElementContent
{
    /**
     * @var CalendarEntry|null|false
     */
    private $calendarEntry;

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Yii::t('CalendarModule.template', 'Calendar event');
    }

    /**
     * @inheritdoc
     */
    protected function getDynamicAttributes(): array
    {
        return [
            'id' => null,
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('CalendarModule.template', 'Select calendar event'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
        ];
    }

    public function isEmpty(): bool
    {
        return parent::isEmpty() || !$this->getCalendarEntry();
    }

    /**
     * @inheritdoc
     */
    public function render($options = [])
    {
        $result = Html::encode($this->getCalendarEntry()->title);

        if ($this->isEditMode($options)) {
            return $this->wrap('span', $result, $options);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function renderEmpty($options = [])
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function getFormView(): string
    {
        return '@calendar/extensions/custom_pages/elements/views/calendar';
    }

    /**
     * @inheritdoc
     */
    public function setAttributes($values, $safeOnly = true)
    {
        if (isset($values['id'])) {
            $values['id'] = is_array($values['id']) ? array_shift($values['id']) : null;
        }

        parent::setAttributes($values, $safeOnly);
    }

    private function getCalendarEntry()
    {
        if ($this->calendarEntry === null) {
            if (!empty($this->id)) {
                $this->calendarEntry = CalendarEntry::findOne($this->id);
            }
            if (!$this->calendarEntry) {
                $this->calendarEntry = false;
            }
        }

        return $this->calendarEntry;
    }
}
