<?php

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

return [
	'BlueSpicePermissionManager' => function ( MediaWikiServices $services ) {
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
