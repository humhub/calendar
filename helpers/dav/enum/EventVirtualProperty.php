<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2025 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\helpers\dav\enum;

enum EventVirtualProperty: string
{
    case DESCRIPTION_NORMALIZED = 'DESCRIPTION_NORMALIZED';
    case ALL_DAY = 'ALL_DAY';
}
