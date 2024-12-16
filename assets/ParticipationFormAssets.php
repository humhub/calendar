<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\assets;

use yii\web\AssetBundle;

class ParticipationFormAssets extends AssetBundle
{
    public $defer = true;

    /**
     * @inheritdoc
     */
    public $publishOptions = [
        'forceCopy' => false,
    ];

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
