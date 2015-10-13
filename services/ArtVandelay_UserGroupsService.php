<?php

namespace Craft;

/**
 * Class ArtVandelay_GlobalsService
 * @package Craft
 */
class ArtVandelay_UserPermissionsService extends BaseApplicationComponent
{

    public function import($userPermissions)
    {
        return new ArtVandelay_ResultModel();
    }

    /**
     * @param UserGroupModel[] $groups
     * @return array
     */
    public function export(array $groups)
    {
        $groupDefinitions = array();

        foreach($groups as $group) {
            $permissionDefinitions = array();
            $groupPermissions = craft()->userPermissions->getGroupPermissionsByUserId($group->id);

            foreach($groupPermissions as $permission) {
                print_r($permission);
            }

            $groupDefinitions[$group->name] = $permissionDefinitions;
        }
        return $groupDefinitions;
    }

}