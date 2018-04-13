<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

/**
 * Created by PhpStorm.
 * User: buddha
 * Date: 14.09.2017
 * Time: 20:44
 */

namespace humhub\modules\calendar\integration;

use DateTime;
use humhub\modules\calendar\interfaces\AbstractCalendarQuery;
use humhub\modules\calendar\interfaces\FilterNotSupportedException;
use humhub\modules\content\components\ActiveQueryContent;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\Profile;
use humhub\modules\user\models\User;
use Yii;
use yii\db\Query;

/**
 * Class BirthdayCalendarQuery
 * @package humhub\modules\calendar\integration
 */
class BirthdayCalendarQuery extends AbstractCalendarQuery
{
    /**
     * @inheritdoc
     */
    protected static $recordClass = Profile::class;

    public $startField = 'birthday';
    public $endField = 'birthday';
    public $dateFormat = 'Y-m-d';

    protected function setupDateCriteria()
    {
        $this->_query->andWhere('MONTH(birthday) >= '.$this->_from->format('m'))->andWhere('MONTH(birthday) <= '.$this->_to->format('m'));
    }

    protected function preFilter($result = [])
    {
        $filtered = [];

        foreach ($result as $profile) {
            /** @var $profile Profile **/
            $birthdayThisYear = static::toCurrentYear($profile->birthday);

            if($birthdayThisYear >= $this->_from && $birthdayThisYear <= $this->_to) {
                $filtered[] = $profile;
            }
        }

        return $filtered;
    }

    protected function filterDashboard()
    {
        if (!Yii::$app->user->isGuest && Yii::$app->getModule('friendship')->isEnabled) {
            $this->_query->joinWith('user')->innerJoin('user_friendship', 'user.id=user_friendship.friend_user_id AND user_friendship.user_id=:userId', [':userId' => Yii::$app->user->id]);
        } else {
            throw new FilterNotSupportedException('Global filter not supported for this query');
        }
    }

    protected function filterUserRelated()
    {
        if(!empty($this->_userScopes) && !(in_array(ActiveQueryContent::USER_RELATED_SCOPE_FOLLOWED_USERS, $this->_userScopes) || in_array(ActiveQueryContent::USER_RELATED_SCOPE_OWN_PROFILE, $this->_userScopes))) {
            throw new FilterNotSupportedException('Non supported user related filters');
        }

        $conditions = ['or'];
        foreach ($this->_userScopes as $userScope) {
            switch ($userScope) {
                case ActiveQueryContent::USER_RELATED_SCOPE_FOLLOWED_USERS:
                   $this->filterFollowedUsersCondition($conditions);
                    break;
                case ActiveQueryContent::USER_RELATED_SCOPE_OWN_PROFILE:
                    if(!$this->hasFilter(static::FILTER_MINE)) {
                        $conditions[] = ['profile.user_id' => Yii::$app->user->id];
                    }
                    break;
            }
        }

        $this->_query->andWhere($conditions);
    }

    protected function filterFollowedUsersCondition(&$conditions = [])
    {
        $friendshipSubQuery = (new Query())->select('user_friendship.friend_user_id')->from('user_friendship')->where(['user_friendship.user_id' => Yii::$app->user->id]);
        $followerSubQuery = (new Query())->select('user_follow.object_id')->from('user_follow')->where(['user_follow.user_id' => Yii::$app->user->id])->andWhere(['object_model' => User::class]);

        if (!Yii::$app->user->isGuest && Yii::$app->getModule('friendship')->isEnabled) {
            $conditions[] = ['in', 'profile.user_id', $friendshipSubQuery ];
            $conditions[] =  ['in', 'profile.user_id', $followerSubQuery ];
        } else {
            $conditions[] = ['in', 'profile.user_id', $followerSubQuery ];
        }
    }

    protected function filterContentContainer()
    {
        if(!$this->_container instanceof Space) {
            return parent::filterContentContainer();
        }

        $spaceMemberships = (new Query())
            ->select("space.id")
            ->from('space_membership')
            ->leftJoin('space', 'space.id=space_membership.space_id')
            ->where('space.id=:spaceId')
            ->andWhere('space_membership.user_id=user.id AND space_membership.status=' . Membership::STATUS_MEMBER)->params([
                ':spaceId' => $this->_container->id
            ]);

        $this->_query->joinWith('user')->andWhere(['exists', $spaceMemberships]);
    }

    public function filterMine()
    {
        $this->_query->andWhere(['profile.user_id' => Yii::$app->user->id]);
    }

    public function setUpRelations()
    {
        $this->_query->with('user');
    }

    public static function toCurrentYear($birthday){
        $suppliedDate = new DateTime($birthday);
        $currentYear = (int)(new DateTime())->format('Y');
        return (new DateTime())->setDate($currentYear, (int)$suppliedDate->format('m'), (int)$suppliedDate->format('d'));
    }
}