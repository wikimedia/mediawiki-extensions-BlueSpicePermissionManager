<?php

namespace BlueSpice\PermissionManager\DynamicSettings;

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

	/**
	 *
	 * @inheritDoc
	 */
	protected function getMaxNoOfBackups() {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'bsg' );
		return $config->get( "PermissionManagerMaxBackups" );
	}
}
