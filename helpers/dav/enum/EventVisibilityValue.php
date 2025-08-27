<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2025 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\helpers\dav\enum;

use humhub\modules\content\models\Content;

enum EventVisibilityValue: string
{
    case PUBLIC = 'PUBLIC';
    case CONFIDENTIAL = 'CONFIDENTIAL';
    case PRIVATE = 'PRIVATE';

    public function contentType(): string
    {
        return match ($this) {
            EventVisibilityValue::PRIVATE => Content::VISIBILITY_PRIVATE,
            EventVisibilityValue::PUBLIC => Content::VISIBILITY_PUBLIC,
            EventVisibilityValue::CONFIDENTIAL => Content::VISIBILITY_OWNER,
        };
    }
}
