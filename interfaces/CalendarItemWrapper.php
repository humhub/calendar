<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 14.09.2017
 * Time: 17:16
 */

namespace humhub\modules\calendar\interfaces;


use humhub\widgets\Label;
use Yii;
use \DateTime;
use yii\base\Object;
use yii\helpers\Html;

class CalendarItemWrapper extends Object implements CalendarItem
{
    const OPTION_START = 'start';
    const OPTION_END = 'end';
    const OPTION_TITLE = 'title';
    const OPTION_COLOR = 'color';
    const OPTION_ALL_DAY = 'allDay';
    const OPTION_UPDATE_URL = 'updateUrl';
    const OPTION_VIEW_URL = 'viewUrl';
    const OPTION_VIEW_MODE = 'viewMode';
    const OPTION_OPEN_URL = 'openUrl';
    const OPTION_ICON = 'icon';
    const OPTION_BADGE = 'badge';
    const OPTION_EDITABLE = 'editable';
    const OPTION_TIMEZONE = 'timezone';

    const VIEW_MODE_MODAL = 'modal';
    const VIEW_MODE_BLANK = 'blank';

    /**
     * @var CalendarItemType
     */
    public $itemType;

    /**
     * @var array
     */
    public $options = [];

    /**
     * @inheritdoc
     */
    public function getFullCalendarArray()
    {
        return [
            'title' => Html::encode($this->getTitle()),
            'editable' => $this->isEditable(),
            'backgroundColor' => Html::encode($this->getColor()),
            'allDay' => $this->isAllDay(),
            'updateUrl' => $this->getUpdateUrl(),
            'viewUrl' => $this->getViewUrl(),
            'viewMode' => $this->getViewMode(),
            'start' => Yii::$app->formatter->asDatetime($this->getStartDateTime(), 'php:c'),
            'end' => Yii::$app->formatter->asDatetime($this->getEndDateTime(), 'php:c'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getStartDateTime()
    {
        return $this->getOption(static::OPTION_START, new DateTime());
    }

    /**
     * @inheritdoc
     */
    public function getEndDateTime()
    {
        return $this->getOption(static::OPTION_END, new DateTime());
    }

    /**
     * @inheritdoc
     */
    public function getTimezone()
    {
        return $this->getOption(static::OPTION_TIMEZONE, Yii::$app->timeZone);
    }

    public function isEditable()
    {
        return $this->getOption(static::OPTION_EDITABLE, false);
    }

    public function getModelId()
    {
        return $this->getOption(static::OPTION_TITLE, $this->itemType->getTitle());
    }

    public function getTitle()
    {
        return $this->getOption(static::OPTION_TITLE, $this->itemType->getTitle());
    }

    public function getColor()
    {
        return $this->getOption(static::OPTION_COLOR, $this->itemType->getColor());
    }

    public function isAllDay()
    {
        if($this->getOption(static::OPTION_ALL_DAY, $this->itemType->isAllDay())) {
            return true;
        } else {
            return false;
        }
    }

    public function getUpdateUrl()
    {
        return $this->getOption(static::OPTION_UPDATE_URL, null);
    }

    protected function getViewMode()
    {
        return $this->getOption(static::OPTION_VIEW_MODE, static::VIEW_MODE_MODAL);
    }

    protected function getViewUrl()
    {
        return $this->getOption(static::OPTION_VIEW_URL, null);
    }

    protected function getOption($key, $default, $options = null)
    {
        $options = (empty($options)) ? $this->options : $options;
        return isset($options[$key]) ? $options[$key] : $default;
    }

    /**
     * @inheritdoc
     */
    public function getUrl()
    {
        return $this->getOption(static::OPTION_OPEN_URL, null);
    }

    /**
     * @inheritdoc
     */
    public function getBadge()
    {
        return $this->getOption(static::OPTION_BADGE, Label::asColor($this->getColor(), $this->itemType->getTitle())->icon($this->getIcon())->right());
    }

    /**
     * @inheritdoc
     */
    public function getIcon()
    {
        return $this->getOption(static::OPTION_ICON, $this->itemType->getIcon());
    }
}