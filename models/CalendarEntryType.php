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
 * Date: 23.07.2017
 * Time: 16:55
 */

namespace humhub\modules\calendar\models;


use humhub\modules\content\models\ContentTag;

class CalendarEntryType extends ContentTag
{
    public $moduleId = 'calendar';

    public function init()
    {
        // Default color
        $this->color = '#59D6E4';
        parent::init();
    }

}