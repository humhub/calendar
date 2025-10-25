<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2025 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\calendar\helpers\dav;

use Sabre\DAV\PropPatch;
use Sabre\DAVACL\PrincipalBackend\AbstractBackend;
use humhub\modules\user\models\User;
use Sabre\DAV\Exception\MethodNotAllowed;
use humhub\modules\user\models\UserFilter;
use yii\helpers\ArrayHelper;

class PrincipalBackend extends AbstractBackend
{
    public function getPrincipalsByPrefix($prefix)
    {
        if (!YII_DEBUG) {
            return [];
        }

        return ArrayHelper::getColumn(User::find()->active()->all(), $this->userToPrincipal(...));
    }

    public function getPrincipalByPath($path)
    {
        $username = basename($path);
        $user = User::find()->active()->andWhere(['username' => $username])->one();

        if (!$user) {
            return null;
        }

        return $this->userToPrincipal($user);
    }

    public function updatePrincipal($path, PropPatch $propPatch)
    {
        throw new MethodNotAllowed('Updating principals is not supported.');
    }

    public function getGroupMemberSet($principal)
    {
        return [];
    }

    public function getGroupMembership($principal)
    {
        return [];
    }

    public function setGroupMemberSet($principal, array $members)
    {
        throw new MethodNotAllowed('Group management is not supported.');
    }

    public function searchPrincipals($prefixPath, array $searchProperties, $test = 'allof')
    {
        $principals = [];

        $query = User::find()->active();
        if (isset($searchProperties['{DAV:}displayname'])) {
            $query->andWhere(['like', 'username', $searchProperties['{DAV:}displayname']]);
        }

        $users = $query->all();
        foreach ($users as $user) {
            $principals[] = 'principals/' . $user->username;
        }

        return $principals;
    }

    public function getPrincipalAcl($path)
    {
        // Example: Give read access to users who are part of the calendar
        return [
            [
                'principal' => 'principals/' . $path,
                'privilege' => '{DAV:}read',
                'grant' => true,
            ],
        ];
    }

    public function findByUri($uri, $principalPrefix)
    {
        return parent::findByUri($uri, $principalPrefix);
    }

    private function userToPrincipal(User $user)
    {
        return [
            'uri' => 'principals/' . $user->username,
            '{DAV:}displayname' => $user->displayName,
            '{http://sabredav.org/ns}email-address' => $user->email,
            '{urn:ietf:params:xml:ns:caldav}calendar-home-set' => [
                'href' => '/calendars/' . $user->username . '/',
            ],
            '{urn:ietf:params:xml:ns:caldav}calendar-resource-uri' => [
                ['href' => '/calendars/' . $user->username . '/'],
            ],
        ];
    }
}
