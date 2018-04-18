<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\integration;
use humhub\modules\user\models\User;


/**
 * Class BirthdayProfileModel
 * @package humhub\modules\calendar\integration
 */
class BirthdayUserModel extends User
{
    public $next_birthday;

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return array_merge(parent::attributes(), ['next_birthday']);
    }

}