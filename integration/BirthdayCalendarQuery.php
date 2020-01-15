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

use humhub\modules\calendar\interfaces\AbstractCalendarQuery;
use humhub\modules\calendar\interfaces\event\FilterNotSupportedException;
use humhub\modules\content\components\ActiveQueryContent;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use Yii;
use yii\db\Expression;
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
    protected static $recordClass = BirthdayUserModel::class;
    protected $dateQueryType = self::DATE_QUERY_TYPE_DATE;

    public $startField = 'profile.birthday';
    public $endField = 'profile.birthday';

    protected $_orderBy = 'next_birthday ASC';

    protected function setupDateCriteria()
    {
        if(!$this->_to || !$this->_from) {
            throw new FilterNotSupportedException('Global filter not supported for this query');
        }

        $toYear = (int)$this->_to->format('Y');
        $fromYear = (int)$this->_from->format('Y');

        // Check if fromDate and toDate years differs
        if ($toYear == $fromYear) {
            $toOrFromBirthday = "DATE_ADD(profile.birthday, INTERVAL {$fromYear}-YEAR(profile.birthday) YEAR)";
        } else {
            $fromDate = $this->_from->format('Y-m-d');
            $fromDateBirth = "DATE_ADD(profile.birthday, INTERVAL {$fromYear}-YEAR(profile.birthday) YEAR)";
            $toDateBirth = "DATE_ADD(profile.birthday, INTERVAL {$toYear}-YEAR(profile.birthday) YEAR)";
            $toOrFromBirthday = "IF( $fromDateBirth > DATE('{$fromDate}'), {$fromDateBirth}, {$toDateBirth})";
        }

        $this->_query->visible();
        $this->_query->joinWith('profile');
        $this->_query->addSelect(['profile.*', 'user.*']);
        $this->_query->addSelect(new Expression($toOrFromBirthday . ' AS next_birthday'));
        $this->_query->andWhere(new Expression($toOrFromBirthday . ' BETWEEN :fromDate AND :toDate'), [':fromDate' => $this->_from->format('Y-m-d'), ':toDate' => $this->_to->format('Y-m-d')]);

    }

    protected function filterDashboard()
    {
        if (!Yii::$app->user->isGuest && Yii::$app->getModule('friendship')->isEnabled) {
            $this->_query->innerJoin('user_friendship', 'user.id=user_friendship.friend_user_id AND user_friendship.user_id=:userId', [':userId' => Yii::$app->user->id]);
        } else {
            throw new FilterNotSupportedException('Global filter not supported for this query');
        }
    }

    protected function filterUserRelated()
    {
        if (!empty($this->_userScopes) && !(in_array(ActiveQueryContent::USER_RELATED_SCOPE_FOLLOWED_USERS, $this->_userScopes) || in_array(ActiveQueryContent::USER_RELATED_SCOPE_OWN_PROFILE, $this->_userScopes))) {
            throw new FilterNotSupportedException('Non supported user related filters');
        }

        $conditions = ['or'];
        foreach ($this->_userScopes as $userScope) {
            switch ($userScope) {
                case ActiveQueryContent::USER_RELATED_SCOPE_FOLLOWED_USERS:
                    $this->filterFollowedUsersCondition($conditions);
                    break;
                case ActiveQueryContent::USER_RELATED_SCOPE_OWN_PROFILE:
                    if (!$this->hasFilter(static::FILTER_MINE)) {
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
            $conditions[] = ['in', 'profile.user_id', $friendshipSubQuery];
            $conditions[] = ['in', 'profile.user_id', $followerSubQuery];
        } else {
            $conditions[] = ['in', 'profile.user_id', $followerSubQuery];
        }
    }

    protected function filterContentContainer()
    {
        if (!$this->_container instanceof Space) {
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

        $this->_query->andWhere(['exists', $spaceMemberships]);
    }

    public function filterMine()
    {
        $this->_query->andWhere(['profile.user_id' => Yii::$app->user->id]);
    }
}