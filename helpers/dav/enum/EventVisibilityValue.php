<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2025 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\helpers\dav\enum;

enum EventVisibilityValue: string
{
    case PUBLIC = 'PUBLIC';
    case CONFIDENTIAL = 'CONFIDENTIAL';
    case PRIVATE = 'PRIVATE';
}
