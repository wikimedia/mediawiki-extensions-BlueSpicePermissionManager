<?php

use MediaWiki\Json\FormatJson;
use MediaWiki\Maintenance\Maintenance;

require_once __DIR__ . '/../../../maintenance/Maintenance.php';

class FixGroupRolesERM34785 extends Maintenance {

	public function execute() {
		$db = $this->getDB( DB_PRIMARY );
		$row = $db->selectRow(
			'mwstake_dynamic_config',
			[ 'mwdc_serialized', 'mwdc_timestamp' ],
			[
				'mwdc_key' => 'bs-permissionmanager-roles',
				'mwdc_is_active' => 1
			],
			__METHOD__
		);

		if ( $row === false ) {
			$this->output( "No entry for PermissionManagerFound\n" );
			return;
		}

		$config = FormatJson::decode( $row->mwdc_serialized, true );
		if ( !isset( $config['bsgGroupRoles'] ) ) {
			$this->output( "No bsgGroupRoles found\n" );
			return;
		}

		$this->output( "CURRENT CONFIG:\n" );
		$this->output( FormatJson::encode( $config, true ) . "\n" );

		$fixedGroupRoles = [];
		foreach ( $config['bsgGroupRoles'] as $groupName => $roleAssignments ) {
			if ( $roleAssignments === null ) {
				$fixedGroupRoles[] = $groupName;
				unset( $config['bsgGroupRoles'][$groupName] );
			}
		}

		if ( empty( $fixedGroupRoles ) ) {
			$this->output( "Nothing found to be fixed\n" );
			return;
		}

		$this->output( "FIXED CONFIG:\n" );
		$this->output( FormatJson::encode( $config, true ) . "\n" );

		$serialized = FormatJson::encode( $config );

		// Disable broken config
		$res = $db->update(
			'mwstake_dynamic_config',
			[ 'mwdc_is_active' => 0 ],
			[
				'mwdc_key' => 'bs-permissionmanager-roles',
				'mwdc_timestamp' => $row->mwdc_timestamp,
			],
			__METHOD__
		);
		if ( $res === false ) {
			$this->output( "Update failed\n" );
			return;
		}

		// Insert and activate fixed config
		$res = $db->insert(
			'mwstake_dynamic_config',
			[
				'mwdc_key' => 'bs-permissionmanager-roles',
				'mwdc_serialized' => $serialized,
				'mwdc_timestamp' => wfTimestampNow(),
				'mwdc_is_active' => 1
			],
			__METHOD__
		);
		if ( $res === false ) {
			$this->output( "Update failed\n" );
			return;
		}

		$fixedGroupRoles = implode( ', ', $fixedGroupRoles );
		$this->output( "Fixed GroupRoles: $fixedGroupRoles" );
	}
}

$maintClass = FixGroupRolesERM34785::class;
require_once RUN_MAINTENANCE_IF_MAIN;
