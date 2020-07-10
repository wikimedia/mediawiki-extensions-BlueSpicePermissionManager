<?php

namespace BlueSpice\PermissionManager\DynamicSettings;

use BlueSpice\DynamicSettings\BSConfigDirSettingsFile;
use BlueSpice\PermissionManager\IPreset;
use BlueSpice\PermissionManager\PermissionManager;
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
		/** @var PermissionManager $permissionManager */
		$permissionManager = MediaWikiServices::getInstance()->getService(
			'BlueSpicePermissionManager'
		);
		$preset = $permissionManager->getActivePreset();
		if ( $preset instanceof IPreset && $preset->getId() === 'custom' ) {
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
