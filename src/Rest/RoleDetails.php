<?php

namespace BlueSpice\PermissionManager\Rest;

use BlueSpice\PermissionManager\PermissionManager as BSPermissionManager;
use MediaWiki\Message\Message;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\Response;
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

	/**
	 * @return Response
	 */
	public function execute() {
		$params = $this->getValidatedParams();
		$queryParams = $this->getRequest()->getQueryParams();
		$role = $params['role'];
		$roleObject = $this->permissionManager->getRoleManager()->getRole( $role );
		if ( !$roleObject ) {
			throw new HttpException( 'role-not-found', 404 );
		}
		$permissions = $roleObject->getPermissions();
		$res = [];
		$start = $queryParams['start'];
		// Next paginated page/batch to be fetched.
		$batch = $start + $queryParams['limit'];

		for ( $i = $start; $i < $batch; ++$i ) {
			$msg = Message::newFromKey( 'right-' . $permissions[$i] );
			// On the last batch, even if we have fewer permissions than the limit,
			// show only those entries and avoid showing useless messages that clutter
			// the dialog.
			if ( $i < count( $permissions ) ) {
				$description = $msg->exists() ? $msg->parse() : '-';
				$res[] = [
					'permission' => $permissions[$i],
					'description' => $description
				];
			}
		}
		return $this->getResponseFactory()->createJson( [ 'results' => $res, 'total' => count( $permissions ) ] );
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
