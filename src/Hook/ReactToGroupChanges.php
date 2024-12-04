<?php

namespace BlueSpice\PermissionManager\Hook;

use BlueSpice\PermissionManager\PermissionManager;
use MediaWiki\Config\ConfigFactory;
use MediaWiki\Permissions\Authority;

class ReactToGroupChanges implements BSPermissionManagerGroupEditedHook, BSPermissionManagerGroupDeletedHook {

	/**
	 * @var ConfigFactory
	 */
	protected ConfigFactory $configFactory;

	/**
	 * @var PermissionManager
	 */
	protected PermissionManager $bsPermissionManager;

	/**
	 * @param ConfigFactory $configFactory
	 * @param PermissionManager $bsPermissionManager
	 */
	public function __construct( ConfigFactory $configFactory, PermissionManager $bsPermissionManager ) {
		$this->configFactory = $configFactory;
		$this->bsPermissionManager = $bsPermissionManager;
	}

	/**
	 * @param string $name
	 * @param Authority $actor
	 * @return void
	 */
	public function onBSPermissionManagerGroupDeleted( string $name, Authority $actor ) {
		[ $groupRoles, $namespaceLockdown ] = $this->getPermissionConfig();
		if ( isset( $groupRoles[$name] ) ) {
			unset( $groupRoles[$name] );
		}

		foreach ( $namespaceLockdown as $ns => &$roles ) {
			foreach ( $roles as $role => &$groups ) {
				if ( in_array( $name, $groups ) ) {
					if ( count( $groups ) === 1 ) {
						unset( $namespaceLockdown[$ns][$role] );
						continue;
					}
					$groups = array_values( array_diff( $groups, [ $name ] ) );
				}
			}
		}

		$this->save( $groupRoles, $namespaceLockdown );
	}

	/**
	 * @param string $oldName
	 * @param string $newName
	 * @param Authority $actor
	 * @return void
	 */
	public function onBSPermissionManagerGroupEdited( string $oldName, string $newName, Authority $actor ) {
		[ $groupRoles, $namespaceLockdown ] = $this->getPermissionConfig();
		if ( isset( $groupRoles[$oldName] ) ) {
			unset( $groupRoles[$oldName] );
		}

		foreach ( $namespaceLockdown as $ns => &$roles ) {
			foreach ( $roles as $role => &$groups ) {
				if ( in_array( $oldName, $groups ) ) {
					$index = array_search( $oldName, $groups );
					if ( $index !== false ) {
						array_splice( $groups, $index, 1, [ $newName ] );
					}
				}
			}
		}
		$this->save( $groupRoles, $namespaceLockdown );
	}

	/**
	 * @return array
	 */
	private function getPermissionConfig(): array {
		$config = $this->configFactory->makeConfig( 'bsg' );
		$groupRoles = $config->get( 'GroupRoles' );
		$namespaceLockdown = $config->get( 'NamespaceRolesLockdown' );

		return [ $groupRoles, $namespaceLockdown ];
	}

	/**
	 * @param array $groupRoles
	 * @param array $namespaceLockdown
	 * @return void
	 */
	private function save( array $groupRoles, array $namespaceLockdown ): void {
		$data = [ 'groupRoles' => $groupRoles, 'roleLockdown' => $namespaceLockdown ];
		$this->bsPermissionManager->saveRoles( $data );
	}
}
