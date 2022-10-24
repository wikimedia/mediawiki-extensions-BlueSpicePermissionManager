<?php

namespace BlueSpice\PermissionManager\Api;

use Wikimedia\ParamValidator\ParamValidator;

class RolePermissionsStore extends \BSApiExtJSStoreBase {

	/**
	 *
	 * @return string
	 */
	protected function getRequiredPermissions() {
		return 'wikiadmin';
	}

	/**
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		$params = parent::getAllowedParams();
		$params[ 'role' ] = [
			ParamValidator::PARAM_TYPE => 'string',
			ParamValidator::PARAM_REQUIRED => true,
		];
		return $params;
	}

	/**
	 *
	 * @param string $query
	 * @return \stdClass[]
	 */
	protected function makeData( $query = '' ) {
		$role = $this->getParameter( 'role' );

		/** @var \BlueSpice\PermissionManager\PermissionManager $permissionManager */
		$permissionManager = $this->services->getService( 'BlueSpicePermissionManager' );
		$permissions = $permissionManager->getRolePermissions( $role, true );

		$result = [];
		foreach ( $permissions as $permission => $desc ) {
			$result[] = (object)[
				'permission_name' => $permission,
				'permission_desc' => $desc
			];
		}

		return $result;
	}

}
