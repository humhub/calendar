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
 * Time: 19:21
 */

namespace humhub\modules\calendar\interfaces\event;


use yii\base\Exception;

class FilterNotSupportedException extends Exception
{
    /**
     * @inheritdoc
     */
    public function getName() {
        return 'Filter not suppored';
    }

}