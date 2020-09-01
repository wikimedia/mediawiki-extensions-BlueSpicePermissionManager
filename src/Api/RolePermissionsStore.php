<?php

namespace BlueSpice\PermissionManager\Api;

use MediaWiki\MediaWikiServices;

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
			\ApiBase::PARAM_TYPE => 'string',
			\ApiBase::PARAM_REQUIRED => true,
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
		$permissionManager = MediaWikiServices::getInstance()->getService(
			'BlueSpicePermissionManager'
		);
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
