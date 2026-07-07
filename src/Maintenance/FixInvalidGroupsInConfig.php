<?php

namespace BlueSpice\PermissionManager\Maintenance;

use MediaWiki\Maintenance\LoggedUpdateMaintenance;
use MWStake\MediaWiki\Component\DynamicConfig\DynamicConfigManager;
use MWStake\MediaWiki\Component\Utils\UtilityFactory;

require_once dirname( __DIR__, 4 ) . '/maintenance/Maintenance.php';

/**
 * Cleanup assignments of non-existing groups in bs-permissionmanager-roles config
 * ERM45086
 */
class FixInvalidGroupsInConfig extends LoggedUpdateMaintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Remove non-existing groups in bs-permissionmanager-roles config' );
	}

	/**
	 * @return bool
	 */
	protected function doDBUpdates() {
		/** @var DynamicConfigManager $manager */
		$manager = $this->getServiceContainer()->getService( 'MWStakeDynamicConfigManager' );
		$config = $manager->getConfigObject( 'bs-permissionmanager-roles' );
		$raw = $manager->retrieveRaw( $config );
		if ( !$raw ) {
			return false;
		}
		$data = json_decode( $raw, true );
		if ( !$data ) {
			$this->output( "No valid config found.\n" );
			return false;
		}
		$fixes = 0;

		$validGroups = $this->getGroups( $manager );
		foreach ( $data['bsgGroupRoles'] ?? [] as $group => $roles ) {
			if ( !in_array( $group, $validGroups ) ) {
				$this->output( "Removing invalid group '$group' from config.\n" );
				$fixes++;
				unset( $data['bsgGroupRoles'][$group] );
			}
		}

		$validLockdowns = [];
		foreach ( $data['bsgNamespaceRolesLockdown'] ?? [] as $ns => $roleConf ) {
			if ( $ns === null || $roleConf === null ) {
				$fixes++;
				continue;
			}
			$validLockdowns[$ns] = $roleConf;
			foreach ( $roleConf as $role => $assignedGroups ) {
				$validAssignedGroups = [];
				foreach ( $assignedGroups as $group ) {
					if ( !in_array( $group, $validGroups ) ) {
						$this->output(
							"Removing group '$group' from namespace lockdown for namespace '$ns' and role '$role'.\n"
						);
						$fixes++;
					} else {
						$validAssignedGroups[] = $group;
					}
				}
				if ( !empty( $validAssignedGroups ) ) {
					$validLockdowns[$ns][$role] = $validAssignedGroups;
				} else {
					unset( $validLockdowns[$ns][$role] );
				}
			}
			if ( empty( $validLockdowns[$ns] ) ) {
				unset( $validLockdowns[$ns] );
			}
		}
		$data['bsgNamespaceRolesLockdown'] = $validLockdowns;

		if ( !$fixes ) {
			$this->output( "No invalid groups found in config.\n" );
			return true;
		}

		$res = $manager->storeConfig( $config, [], json_encode( $data ) );
		if ( $res ) {
			$this->output( "Successfully fixed invalid groups in config.\n" );
			return true;
		} else {
			$this->output( "Failed to fix invalid groups in config.\n" );
			return false;
		}
	}

	/**
	 * @return string
	 */
	protected function getUpdateKey() {
		return 'bs-permissionmanager-fix-invalid-groups';
	}

	/**
	 * @param DynamicConfigManager $manager
	 * @return array
	 */
	private function getGroups( DynamicConfigManager $manager ): array {
		/** @var UtilityFactory $utilsFactory */
		$utilsFactory = $this->getServiceContainer()->getService( 'MWStakeCommonUtilsFactory' );

		// We must use this service here, as just by being in the PM config group will "become real".
		// This service filters it out
		return array_merge(
			$utilsFactory->getGroupHelper()->getAvailableGroups(),
			[ '*', 'user', 'bureaucrat', 'bot' ]
		);
	}
}

$maintClass = FixInvalidGroupsInConfig::class;
require_once RUN_MAINTENANCE_IF_MAIN;
