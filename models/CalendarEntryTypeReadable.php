<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\models;

use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\user\helpers\AuthHelper;
use humhub\modules\user\models\User;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Expression;

/**
 * Class CalendarEntryTypeReadable to find only event types that are readable by current user
 *
 * @since 1.6.4
 */
class CalendarEntryTypeReadable extends CalendarEntryType
{
    /**
     * @inheritdoc
     */
    public static function find()
    {
        return static::addReadableCondition(parent::find());
    }

    /**
     * Filter the content tag query to containers(Space/User) that are viewable by current user
     *
     * @param ActiveQuery $query
     * @return ActiveQuery
     */
    public static function addReadableCondition(ActiveQuery $query): ActiveQuery
    {
        $user = Yii::$app->user->getIdentity();

        $query->leftJoin('contentcontainer AS rContainer', 'rContainer.id = content_tag.contentcontainer_id')
            ->leftJoin('space AS rSpace', 'rContainer.pk = rSpace.id AND rContainer.class = :spaceClass', [':spaceClass' => Space::class])
            ->leftJoin('user AS rUser', 'rContainer.pk = rUser.id AND rContainer.class = :userClass', [':userClass' => User::class]);

        $conditions = [
            'global' => ['IS', 'content_tag.contentcontainer_id', new Expression('NULL')],
            'space' => ['AND', ['IS NOT', 'rSpace.id', new Expression('NULL')]],
            'user' => ['AND', ['IS NOT', 'rUser.id', new Expression('NULL')]],
        ];

        if ($user !== null) {
            if (!$user->canViewAllContent(Space::class)) {
                // User must be a space's member OR Space and Content are public
                $query->leftJoin('space_membership', 'rContainer.pk = space_membership.space_id AND rContainer.class = :spaceClass AND space_membership.user_id = :userId', [':userId' => $user->id, ':spaceClass' => Space::class]);
                $conditions['space'][] = [
                    'OR',
                    ['space_membership.status' => Membership::STATUS_MEMBER],
                    ['!=', 'rSpace.visibility', Space::VISIBILITY_NONE],
                ];
            }

            if (!$user->canViewAllContent(User::class)) {
                // User can view only content of own profile
                $conditions['user'][] = ['rUser.id' => $user->id];
            }
        } elseif (AuthHelper::isGuestAccessEnabled()) {
            $conditions['space'][] = ['rSpace.visibility' => Space::VISIBILITY_ALL];
            $conditions['user'][] = ['rUser.visibility' => User::VISIBILITY_ALL];
        } else {
            unset($conditions['space']);
            unset($conditions['user']);
        }

        return $query->andWhere(['OR'] + $conditions);
    }
}
