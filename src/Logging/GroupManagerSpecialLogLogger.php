<?php

namespace BlueSpice\PermissionManager\Logging;

use ManualLogEntry;
use MediaWiki\Permissions\Authority;
use MediaWiki\Title\Title;

class GroupManagerSpecialLogLogger {

	/**
	 * @param string $type
	 * @param Authority $actor
	 * @param array $params
	 * @return void
	 */
	public function log( string $type, Authority $actor, array $params ) {
		$logger = new ManualLogEntry( 'bs-group-manager', $type );
		$logger->setPerformer( $actor->getUser() );
		$logger->setTarget( Title::newMainPage() );
		$logParams = [];
		$paramCount = 3;
		foreach ( $params as $key => $value ) {
			$paramCount++;
			$logParams["$paramCount::$key"] = $value;
		}
		$logger->setParameters( $logParams );
		$logger->insert();
	}
}
