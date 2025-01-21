<?php

namespace BlueSpice\PermissionManager\Rest;

use BlueSpice\PermissionManager\PermissionManager as BSPermissionManager;
use MediaWiki\Context\RequestContext;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;

class SavePermissions extends SimpleHandler {

	/** @var BSPermissionManager */
	private $bsPermissionManager;

	public function __construct( BSPermissionManager $bsPermissionManager ) {
		$this->bsPermissionManager = $bsPermissionManager;
	}

	public function execute() {
		$this->assertUserCan();

		try {
			$data = $this->getValidatedBody();
			$this->bsPermissionManager->saveRoles( $data );
		} catch ( \Throwable $ex ) {
			throw new HttpException( $ex->getMessage(), 500 );
		}
		return $this->getResponseFactory()->create();
	}

	/**
	 * @return true
	 */
	public function needsReadAccess() {
		return true;
	}

	/**
	 * @return array[]
	 */
	public function getBodyParamSettings(): array {
		return [
			'groupRoles' => [
				static::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'array',
				ParamValidator::PARAM_DEFAULT => []
			],
			'roleLockdown' => [
				static::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'array',
				ParamValidator::PARAM_DEFAULT => []
			],
		];
	}

	/**
	 * @return void
	 * @throws HttpException
	 */
	private function assertUserCan() {
		$user = RequestContext::getMain()->getUser();
		if ( !$user->isAllowed( 'wikiadmin' ) ) {
			throw new HttpException( 'Permission denied', 403 );
		}
	}
}
