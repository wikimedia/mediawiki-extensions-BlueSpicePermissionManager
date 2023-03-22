<?php

namespace BlueSpice\PermissionManager\DynamicSettings;

use BlueSpice\Config;
use BlueSpice\DynamicSettings\BSConfigDirSettingsFile;
use MediaWiki\MediaWikiServices;

class PmSettings extends BSConfigDirSettingsFile {

	/**
	 *
	 * @inheritDoc
	 */
	protected function getFilename() {
		return 'pm-settings.php';
	}

	protected function shouldApply() {
		// We intentionally do not use the `BlueSpicePermissionManager` service here, as it would
		// trigger a `PHP Deprecated:  Premature access to service container` error.
		// By this we initialize the database based `BlueSpice\Config` very early, but this has no
		// sideeffect currently. If this changes, we need to find a better solution.
		$presetId = Config::newInstance()->get( 'PermissionManagerActivePreset' );
		if ( $presetId === 'custom' ) {
			return true;
		}

		return false;
	}

	/**
	 *
	 * @inheritDoc
	 */
	protected function getMaxNoOfBackups() {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'bsg' );
		return $config->get( "PermissionManagerMaxBackups" );
	}
}
