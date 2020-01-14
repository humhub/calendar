<?php

namespace humhub\modules\calendar\widgets\mails;

use humhub\modules\calendar\interfaces\event\CalendarEventIF;

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

class CalendarEventMailInfo extends \humhub\components\Widget
{
    const RENDER_TYPE_TEXT = 'text';
    const RENDER_TYPE_HTML = 'html';

    const VIEW_HTML = 'eventInfoHtml';
    const VIEW_Text = 'eventInfoText';

    /**
     * @var CalendarEventIF
     */
    public $event;

    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $type = self::RENDER_TYPE_HTML;

    /**
     * @var
     */
    public $renderType;

    /**
     * @param $event
     * @param null $url
     * @param null $extraInfo
     * @return string
     * @throws \Exception
     */
    public $extraInfo;

    public static function  html($event, $url = null, $extraInfo = null)
    {
        return static::widget([
            'event' => $event,
            'url' => $url,
            'type' => static::RENDER_TYPE_HTML,
            'extraInfo' => $extraInfo
        ]);
    }

    public static function  text($event, $url = null,  $extraInfo = null)
    {
        return static::widget([
            'event' => $event,
            'url' => $url,
            'type' => static::RENDER_TYPE_TEXT,
            'extraInfo' => $extraInfo
        ]);
    }

    public function run()
    {
        $view = $this->type === static::RENDER_TYPE_TEXT ? static::VIEW_Text : static::VIEW_HTML;
        return $this->render($view, ['event' => $this->event, 'url' => $this->url, 'extraInfo' => $this->extraInfo]);
    }
}