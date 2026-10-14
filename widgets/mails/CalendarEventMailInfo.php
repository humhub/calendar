<?php

namespace humhub\modules\calendar\widgets\mails;

use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use humhub\modules\user\models\User;

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

class CalendarEventMailInfo extends \humhub\components\Widget
{
    public const RENDER_TYPE_TEXT = 'text';
    public const RENDER_TYPE_HTML = 'html';

    public const VIEW_HTML = 'eventInfoHtml';
    public const VIEW_Text = 'eventInfoText';

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

    /**
     * @var User|null receiver of the email, used to generate download tokens for inline images
     */
    public $receiver;

    public static function html($event, $url = null, $extraInfo = null, ?User $receiver = null)
    {
        return static::widget([
            'event' => $event,
            'url' => $url,
            'type' => static::RENDER_TYPE_HTML,
            'extraInfo' => $extraInfo,
            'receiver' => $receiver,
        ]);
    }

    public static function text($event, $url = null, $extraInfo = null)
    {
        return static::widget([
            'event' => $event,
            'url' => $url,
            'type' => static::RENDER_TYPE_TEXT,
            'extraInfo' => $extraInfo,
        ]);
    }

    public function run()
    {
        $view = $this->type === static::RENDER_TYPE_TEXT ? static::VIEW_Text : static::VIEW_HTML;
        return $this->render($view, [
            'event' => $this->event,
            'url' => $this->url,
            'extraInfo' => $this->extraInfo,
            'receiver' => $this->receiver,
        ]);
    }
}
