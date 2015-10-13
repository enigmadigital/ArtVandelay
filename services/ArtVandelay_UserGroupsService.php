<?php

namespace Craft;

/**
 * Class ArtVandelay_GlobalsService
 * @package Craft
 */
class ArtVandelay_UserGroupsService extends BaseApplicationComponent
{
    /** @var SectionModel[] */
    private $sections = [];

    //==============================================================================================================
    //=================================================  EXPORT  ===================================================
    //==============================================================================================================

    /**
     * @param UserGroupModel[] $groups
     * @return array
     */
    public function export(array $groups)
    {
        $this->sections = craft()->sections->getAllSections('id');

        $groupDefinitions = array();

        foreach ($groups as $group) {
            $groupDefinitions[$group->handle] = $this->getGroupDefinition($group);
        }

        return $groupDefinitions;
    }

    /**
     * @param UserGroupModel $group
     * @return array
     */
    private function getGroupDefinition(UserGroupModel $group)
    {
        $permissionDefinitions = array();
        $groupPermissions = craft()->userPermissions->getPermissionsByGroupId($group->id);

        foreach ($groupPermissions as $permission) {
            $permissionDefinitions[] = $this->getPermission($permission);;
        }

        return array(
            'name' => $group->name,
            'permissions' => $permissionDefinitions
        );
    }

    /**
     * @param string $permission
     * @return string
     */
    private function getPermission($permission)
    {
        if (strpos($permission, ':') > -1) {
            $permissionArray = explode(':', $permission);
            $section = $this->sections[$permissionArray[1]];
            $permission = $permissionArray[0] . ':' . $section->handle;

        }
        return $permission;
    }

    //==============================================================================================================
    //=================================================  IMPORT  ===================================================
    //==============================================================================================================

    /**
     * @param array $groupDefinitions
     * @param bool $force if set to true items not in the import will be deleted
     * @return ArtVandelay_ResultModel
     */
    public function import(array $groupDefinitions, $force = false)
    {
        $this->sections = craft()->sections->getAllSections('handle');

        $result = new ArtVandelay_ResultModel();
        $userGroups = craft()->userGroups->getAllGroups('handle');

        foreach ($groupDefinitions as $groupHandle => $groupDefinition) {
            $group = array_key_exists($groupHandle, $userGroups)
                ? $userGroups[$groupHandle]
                : new UserGroupModel();
            $group->name = $groupDefinition['name'];
            $group->handle = $groupHandle;

            if (!craft()->userGroups->saveGroup($group)) {
                return $result->error($group->getAllErrors());
            }

            $permissions = $this->getPermissions($groupDefinition['permissions']);

            craft()->userPermissions->saveGroupPermissions($group->id, $permissions);

            unset($userGroups[$groupHandle]);
        }

        if ($force) {
            foreach ($userGroups as $group) {
                craft()->userGroups->deleteGroupById($group->id);
            }
        }
        return $result;
    }

    /**
     * @param array $permissionDefinitions
     * @return array
     */
    private function getPermissions(array $permissionDefinitions)
    {
        $permissions = array();
        foreach ($permissionDefinitions as $permissionDefinition) {
            $permissions[] = $this->getPermission($permissionDefinition);
        }
        return $permissions;
    }

}