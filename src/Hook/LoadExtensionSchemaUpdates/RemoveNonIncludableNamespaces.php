<?php

namespace BlueSpice\PermissionManager\Hook\LoadExtensionSchemaUpdates;

use BlueSpice\Hook\LoadExtensionSchemaUpdates;
use BlueSpice\PermissionManager\Maintenance\RemoveNonIncludableNamespaces as MaintenanceScript;

class RemoveNonIncludableNamespaces extends LoadExtensionSchemaUpdates {

	protected function doProcess() {
		$this->updater->addPostDatabaseUpdateMaintenance(
			MaintenanceScript::class
		);
	}
}
