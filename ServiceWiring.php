<?php

use BlueSpice\PermissionManager\Logging\GroupManagerSpecialLogLogger;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

return [
	'BlueSpicePermissionManager' => static function ( MediaWikiServices $services ) {
		return new BlueSpice\PermissionManager\PermissionManager(
			$services->getService( 'BSRoleManager' ),
			$services,
			$services->getConfigFactory()->makeConfig( 'bsg' ),
			LoggerFactory::getInstance( 'permissionmanager' ),
			$services->getHookContainer(),
			$services->getService( 'MWStakeDynamicConfigManager' )
		);
	},
	'BlueSpice.PermissionManager.GroupManager' => static function ( MediaWikiServices $services ) {
		return new BlueSpice\PermissionManager\GroupManager(
			$services->getService( 'MWStakeDynamicConfigManager' ),
			$services->getDBLoadBalancer(),
			$services->getMainConfig(),
			$services->getHookContainer(),
			LoggerFactory::getInstance( 'BlueSpicePermissionManager.GroupManager' ),
			new GroupManagerSpecialLogLogger()
		);
	},
];
