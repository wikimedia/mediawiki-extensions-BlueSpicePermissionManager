<?php

namespace BlueSpice\PermissionManager\Rest;

use BlueSpice\PermissionManager\PermissionManager as BSPermissionManager;
use MediaWiki\Message\Message;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;

class RoleDetails extends SimpleHandler {

	/**
	 * @param BSPermissionManager $permissionManager
	 */
	public function __construct(
		private readonly BSPermissionManager $permissionManager
	) {
	}

	public function execute() {
		$params = $this->getValidatedParams();
		$role = $params['role'];
		$roleObject = $this->permissionManager->getRoleManager()->getRole( $role );
		if ( !$roleObject ) {
			throw new HttpException( 'role-not-found', 404 );
		}
		$permissions = $roleObject->getPermissions();
		$res = [];
		foreach ( $permissions as $permission ) {
			$msg = Message::newFromKey( 'right-' . $permission );
			$description = $msg->exists() ? $msg->parse() : '-';
			$res[] = [
				'permission' => $permission,
				'description' => $description
			];
		}
		return $this->getResponseFactory()->createJson( [ 'results' => $res, 'total' => count( $res ) ] );
	}

	/**
	 * @return array[]
	 */
	public function getParamSettings() {
		return [
			'role' => [
				static::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false
			],
		];
	}
}
