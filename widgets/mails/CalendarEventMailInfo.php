<?php

namespace humhub\modules\calendar\widgets\mails;

use humhub\modules\calendar\interfaces\event\CalendarEventIF;
use humhub\modules\user\models\User;

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

/**
 * The details of an event (title, time, organizer, location, description) for a mail, as HTML
 * or plain text; what a notification about the event shows below its sentence.
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
    public $type = self::RENDER_TYPE_HTML;

    /**
     * @var string|null additional information shown below the event details, e.g. the participant information
     */
    public $extraInfo;

    /**
     * @var User|null receiver of the email, used to generate download tokens for inline images
     */
    public $receiver;

    /**
     * @param CalendarEventIF $event
     * @param string|null $extraInfo
     * @param User|null $receiver
     * @return string
     * @throws \Exception
     */
    public static function html($event, $extraInfo = null, ?User $receiver = null)
    {
        return static::widget([
            'event' => $event,
            'type' => static::RENDER_TYPE_HTML,
            'extraInfo' => $extraInfo,
            'receiver' => $receiver,
        ]);
    }

    /**
     * @param CalendarEventIF $event
     * @param string|null $extraInfo
     * @return string
     * @throws \Exception
     */
    public static function text($event, $extraInfo = null)
    {
        return static::widget([
            'event' => $event,
            'type' => static::RENDER_TYPE_TEXT,
            'extraInfo' => $extraInfo,
        ]);
    }

    public function run()
    {
        $view = $this->type === static::RENDER_TYPE_TEXT ? static::VIEW_Text : static::VIEW_HTML;
        return $this->render($view, [
            'event' => $this->event,
            'extraInfo' => $this->extraInfo,
            'receiver' => $this->receiver,
        ]);
    }
}
