<?php

namespace BlueSpice\PermissionManager\Hook\BSGroupManagerGroupDeleted;

use BlueSpice\GroupManager\Hook\BSGroupManagerGroupDeleted;
use BlueSpice\PermissionManager\Extension as PermissionManager;

class UnassignRoles extends BSGroupManagerGroupDeleted {

	protected function doProcess() {
		$groupRoles = $this->getConfig()->get( 'GroupRoles' );
		$namespaceLockdown = $this->getConfig()->get( 'NamespaceRolesLockdown' );

		unset( $groupRoles[$this->group] );

		foreach( $namespaceLockdown as $ns => &$roles ) {
			foreach( $roles as $role => &$groups ) {
				if( in_array( $this->group, $groups ) ) {
					if( count( $groups ) === 1 ) {
						unset( $namespaceLockdown[$ns][$role] );
						continue;
					}
					$groups = array_diff( $groups, [$this->group] );
				}
			}
		}

		$data = new \stdClass();
		$data->groupRoles = $groupRoles;
		$data->roleLockdown = $namespaceLockdown;

		$this->result = PermissionManager::saveRoles( $data );
		return true;
	}
}