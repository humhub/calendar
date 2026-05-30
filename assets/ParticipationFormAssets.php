<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\assets;

use humhub\components\assets\AssetBundle;

class ParticipationFormAssets extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $forceCopy = false;

    /**
     * @inheritdoc
     */
    public $sourcePath = '@calendar/resources';

    /**
     * @inheritdoc
     */
    public $js = [
        'js/humhub.calendar.participation.Form.min.js',
    ];
}
