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
                'uri' => 'principals/' . $user->username,
                '{DAV:}displayname' => $user->displayName,
                '{http://sabredav.org/ns}email-address' => $user->email,
                '{urn:ietf:params:xml:ns:caldav}calendar-home-set' => [
                    'href' => '/calendars/' . $user->username . '/'
                ],
                '{urn:ietf:params:xml:ns:caldav}calendar-resource-uri' => [
                    ['href' => '/calendars/' . $user->username . '/']
                ],
            ];
        }

        return $principals;
    }

    public function getPrincipalByPath($path)
    {
        $username = basename($path);
        $user = User::findOne(['username' => $username]);

        if (!$user) {
            return null;
        }

        return [
            'uri' => 'principals/' . $user->username,
            '{DAV:}displayname' => $user->displayName,
            '{http://sabredav.org/ns}email-address' => $user->email,
            '{urn:ietf:params:xml:ns:caldav}calendar-home-set' => [
                'href' => '/calendars/' . $user->username . '/'
            ],
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
            ]
        ];
    }
}