<?php

namespace BlueSpice\PermissionManager\Hook\BSGroupManagerGroupNameChanged;

use BlueSpice\GroupManager\Hook\BSGroupManagerGroupNameChanged;
use MediaWiki\MediaWikiServices;

class ChangeGroupRoleAssignments extends BSGroupManagerGroupNameChanged {

	protected function doProcess() {
		$groupRoles = $this->getConfig()->get( 'GroupRoles' );
		$namespaceLockdown = $this->getConfig()->get( 'NamespaceRolesLockdown' );

		$groupRoles[$this->newGroup] = $groupRoles[$this->group];
		unset( $groupRoles[$this->group] );

		foreach ( $namespaceLockdown as $ns => &$roles ) {
			foreach ( $roles as $role => &$groups ) {
				if ( in_array( $this->group, $groups ) ) {
					$index = array_search( $this->group, $groups );
					if ( $index !== false ) {
						array_splice( $groups, $index, 1, [ $this->newGroup ] );
					}
				}
			}
		}

		$data = new \stdClass();
		$data->groupRoles = $groupRoles;
		$data->roleLockdown = $namespaceLockdown;

		$permissionManager = MediaWikiServices::getInstance()->getService(
			'BlueSpicePermissionManager'
		);

		$this->result = $permissionManager->saveRoles( $data );
		return true;
	}
}
