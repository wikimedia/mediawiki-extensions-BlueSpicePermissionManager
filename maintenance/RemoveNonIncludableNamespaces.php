<?php

namespace BlueSpice\PermissionManager\Maintenance;

$IP = dirname( dirname( dirname( __DIR__ ) ) );

require_once "$IP/maintenance/Maintenance.php";

use BlueSpice\PermissionManager\PermissionManager;
use LoggedUpdateMaintenance;
use MediaWiki\MediaWikiServices;
use stdClass;

class RemoveNonIncludableNamespaces extends LoggedUpdateMaintenance {

	/**
	 * Do the actual work. All child classes will need to implement this.
	 * Return true to log the update as done or false (usually on failure).
	 * @return bool
	 */
	protected function doDBUpdates() {
		$data = new stdClass();
		$data->groupRoles = $GLOBALS['bsgGroupRoles'];
		$data->roleLockdown = $GLOBALS['bsgNamespaceRolesLockdown'];

		/** @var PermissionManager $permissionManager */
		$permissionManager = MediaWikiServices::getInstance()->getService(
		'BlueSpicePermissionManager'
		);
		$res = $permissionManager->saveRoles( $data );
		if ( is_array( $res ) && isset( $res['success'] ) && $res['success'] ) {
			$this->output(
				'Removing non-includable namespaces from pm-settings file... done' . PHP_EOL
			);
			return true;
		}
		$this->output(
			'Removing non-includable namespaces from pm-settings file... failed' . PHP_EOL
		);
		return false;
	}

	/**
	 * Get the update key name to go in the update log table
	 * @return string
	 */
	protected function getUpdateKey() {
		return 'bs_permissionmanager_removenonincludablenamespaces';
	}
}

$maintClass = RemoveNonIncludableNamespaces::class;
require_once RUN_MAINTENANCE_IF_MAIN;
