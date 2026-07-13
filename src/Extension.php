<?php

namespace BlueSpice\PermissionManager;

use MediaWiki\MediaWikiServices;

class Extension extends \BlueSpice\Extension {

	public static function onCallback() {
		// Do not apply permission changes within WMF CI, to avoid issues with core tests
		if ( defined( 'MW_QUIBBLE_CI' ) ) {
			return;
		}

		$GLOBALS['wgHooks']['SetupAfterCache'] = $GLOBALS['wgHooks']['SetupAfterCache'] ?? [];
		array_unshift( $GLOBALS['wgHooks']['SetupAfterCache'], static function () {
			// Earliest that we have DB service available
			/** @var PermissionManager $permissionManager */
			$permissionManager = MediaWikiServices::getInstance()->getService(
				'BlueSpicePermissionManager'
			);

			// Apply preset
			$permissionManager->applyCurrentPreset();
			// Implicitly enable the role system
			$permissionManager->enableRoleSystem();
		} );
	}
}
