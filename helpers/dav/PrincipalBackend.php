<?php

namespace humhub\modules\calendar\helpers\dav;

use Sabre\DAV\PropPatch;
use Sabre\DAVACL\PrincipalBackend\AbstractBackend;
use humhub\modules\user\models\User;
use Sabre\DAV\Exception\MethodNotAllowed;

class PrincipalBackend extends AbstractBackend
{
    public function getPrincipalsByPrefix($prefix)
    {
        $principals = [];

        $users = User::find()->all();
        foreach ($users as $user) {
            $principals[] = [
                'uri' => 'principals/users/' . $user->guid,
                '{DAV:}displayname' => $user->displayName,
                '{http://sabredav.org/ns}email-address' => $user->email,
            ];
        }

        return $principals;
    }

    public function getPrincipalByPath($path)
    {
        $userId = basename($path);
        $user = User::findOne(['guid' => $userId]);

        if (!$user) {
            return null;
        }

        return [
            'uri' => 'principals/users/' . $user->id,
            '{DAV:}displayname' => $user->displayName,
            '{http://sabredav.org/ns}email-address' => $user->email,
        ];
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

        $query = User::find();
        if (isset($searchProperties['{DAV:}displayname'])) {
            $query->andWhere(['like', 'username', $searchProperties['{DAV:}displayname']]);
        }

        $users = $query->all();
        foreach ($users as $user) {
            $principals[] = 'principals/users/' . $user->id;
        }

        return $principals;
    }
}