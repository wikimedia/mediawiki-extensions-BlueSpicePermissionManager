<?php

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

return [
	'BlueSpicePermissionManager' => static function ( MediaWikiServices $services ) {
		return new BlueSpice\PermissionManager\PermissionManager(
			$services->getService( 'BSPermissionRegistry' ),
			$services->getService( 'BSRoleManager' ),
			$services,
			$services->getConfigFactory()->makeConfig( 'bsg' ),
			LoggerFactory::getInstance( 'permissionmanager' ),
			$services->getHookContainer()
		);
	}
];
