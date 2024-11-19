<?php

namespace BlueSpice\PermissionManager\Hook;

use BlueSpice\PermissionManager\Maintenance\MigrateGmSettings;
use BlueSpice\PermissionManager\Maintenance\MigratePmSettings;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

/**
 * This should probably be removed for 5.0
 */
class MigrateSettings implements LoadExtensionSchemaUpdatesHook {

	/**
	 * @inheritDoc
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$updater->addPostDatabaseUpdateMaintenance(
			MigratePmSettings::class
		);
		$updater->addPostDatabaseUpdateMaintenance(
			MigrateGmSettings::class
		);
	}
}
