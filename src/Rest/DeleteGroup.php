<?php

namespace BlueSpice\PermissionManager\Rest;

use BlueSpice\PermissionManager\GroupManager;
use MediaWiki\Context\RequestContext;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\SimpleHandler;
use Throwable;
use Wikimedia\ParamValidator\ParamValidator;

class DeleteGroup extends SimpleHandler {

	/** @var GroupManager */
	private GroupManager $groupManager;

	/**
	 * @param GroupManager $groupManager
	 */
	public function __construct( GroupManager $groupManager ) {
		$this->groupManager = $groupManager;
	}

	/**
	 * @return true
	 */
	public function needsWriteAccess() {
		return true;
	}

	public function execute() {
		$params = $this->getValidatedParams();
		try {
			$this->groupManager->removeGroup(
				$params['name'], RequestContext::getMain()->getAuthority()
			);
		} catch ( Throwable $e ) {
			error_log( $e->getTraceAsString() );
			throw new HttpException( $e->getMessage(), 500 );
		}
		return $this->getResponseFactory()->createNoContent();
	}

	public function getParamSettings(): array {
		return [
			'name' => [
				static::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			]
		];
	}
}
