<?php

namespace BlueSpice\PermissionManager\Hook\LoadExtensionSchemaUpdates;

use BlueSpice\Hook\LoadExtensionSchemaUpdates;
use BlueSpice\PermissionManager\Maintenance\MigratePmSettings as MaintenanceScript;

class MigratePmSettings extends LoadExtensionSchemaUpdates {

	protected function doProcess() {
		$this->updater->addPostDatabaseUpdateMaintenance(
			MaintenanceScript::class
		);
	}
}
