<?php

namespace BlueSpice\PermissionManager\Api;

use \BlueSpice\PermissionManager\Extension as PermissionManager;

class RolePermissionsStore extends \BSApiExtJSStoreBase {

	protected function getRequiredPermissions() {
		return 'wikiadmin';
	}

	public function getAllowedParams() {
		$params = parent::getAllowedParams();
		$params[ 'role' ] = [
			\ApiBase::PARAM_TYPE => 'string',
			\ApiBase::PARAM_REQUIRED => true,
			\ApiBase::PARAM_DFLT => ''
		];
		return $params;
	}

	protected function makeData( $query = '' ) {
		$role = $this->getParameter( 'role' );
		$permissions = PermissionManager::getRolePermissions( $role, true );

		$result = [];
		foreach( $permissions as $permission => $desc ) {
			$result[] = (object) [
				'permission_name' => $permission,
				'permission_desc' => $desc
			];
		}

		return $result;
	}

}