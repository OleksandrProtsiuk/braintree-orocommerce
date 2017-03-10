<?php

namespace Oro\Bundle\CustomerProBundle\Datagrid;

use Oro\Bundle\CustomerBundle\Datagrid\RolePermissionDatasource as BaseRolePermissionDatasource;

class RolePermissionDatasource extends BaseRolePermissionDatasource
{
    /** @var array */
    protected $excludePermissions = [];

    /**
     * @param string $permissionName
     */
    public function addExcludePermission($permissionName)
    {
        $this->excludePermissions[] = $permissionName;
    }

    /**
     * @param string $permissionName
     * @return bool
     */
    protected function isSupportedPermission($permissionName)
    {
        return !in_array($permissionName, $this->excludePermissions, true);
    }
}
