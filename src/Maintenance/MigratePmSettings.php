<?php

namespace BlueSpice\PermissionManager\Maintenance;

use Exception;
use MediaWiki\Maintenance\LoggedUpdateMaintenance;
use MediaWiki\MediaWikiServices;
use MWStake\MediaWiki\Component\DynamicConfig\DynamicConfigManager;

require_once dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/maintenance/Maintenance.php';

class MigratePmSettings extends LoggedUpdateMaintenance {
	/**
	 * @inheritDoc
	 */
	protected function doDBUpdates() {
		/** @var DynamicConfigManager $configManager */
		$configManager = MediaWikiServices::getInstance()->getService( 'MWStakeDynamicConfigManager' );
		if ( $this->isMigrated( $configManager ) ) {
			$this->output( 'New settings already migrated. Nothing to do.' );
			return true;
		}
		if ( !defined( 'BS_LEGACY_CONFIGDIR' ) ) {
			$this->output( 'BS_LEGACY_CONFIGDIR not defined. Nothing to do.' );
			return true;
		}
		$parsed = $this->parseOldSettings();
		if ( !$parsed ) {
			return true;
		}
		return $this->storeSettings( $parsed, $configManager );
	}

	/**
	 * @inheritDoc
	 */
	protected function getUpdateKey() {
		return 'permission-manager-migrate-pm-settings';
	}

	/**
	 * @param DynamicConfigManager $configManager
	 *
	 * @return bool
	 * @throws Exception
	 */
	private function isMigrated( DynamicConfigManager $configManager ) {
		$config = $configManager->getConfigObject( 'bs-permissionmanager-roles' );
		if ( !$config ) {
			throw new Exception( 'Dynamic config for PermissionManager not found' );
		}
		$data = $configManager->retrieveRaw( $config );
		return $data !== null;
	}

	/**
	 * @return array|null
	 */
	private function parseOldSettings(): ?array {
		$path = BS_LEGACY_CONFIGDIR . '/pm-settings.php';
		if ( !file_exists( $path ) ) {
			$this->output( "Old settings file not found. Nothing to do.\n" );
			return null;
		}
		return $this->doParse( file_get_contents( $path ) );
	}

	/**
	 * @param string|false $source
	 *
	 * @return array|null
	 */
	private function doParse( $source ): ?array {
		if ( $source === false ) {
			$this->output( "Could not read old settings file\n" );
			return null;
		}
		$groupRoles = $this->parseGroupRoles( $source );
		$namespaceLockdown = $this->parseNamespaceLockdown( $source );
		return [ 'bsgGroupRoles' => $groupRoles, 'bsgNamespaceRolesLockdown' => $namespaceLockdown ];
	}

	/**
	 * @param array $parsed
	 * @param DynamicConfigManager $configManager
	 *
	 * @return false
	 */
	private function storeSettings( array $parsed, DynamicConfigManager $configManager ): bool {
		$config = $configManager->getConfigObject( 'bs-permissionmanager-roles' );
		return $configManager->storeConfig( $config, [], json_encode( $parsed ) );
	}

	/**
	 * @param string $source
	 *
	 * @return array
	 */
	private function parseGroupRoles( string $source ) {
		$regexes = [
			'/\$GLOBALS\[\'bsgGroupRoles\'\]\[\'(.*?)\'\]\[\'(.*?)\'\]\s=\s(.*?);/'
		];
		$roles = [];
		foreach ( $regexes as $regex ) {
			$roles = array_merge( $this->parseRolesFromRegex( $regex, $source ), $roles );
		}
		return $roles;
	}

	/**
	 * @param string $regex
	 * @param string $source
	 *
	 * @return array
	 */
	private function parseRolesFromRegex( string $regex, string $source ) {
		$roles = [];
		$matches = [];
		$hasMatches = preg_match_all(
			$regex, $source, $matches
		);
		if ( !$hasMatches ) {
			return $roles;
		}
		foreach ( $matches[1] as $i => $name ) {
			if ( !isset( $roles[$name] ) ) {
				$roles[$name] = [];
			}
			$group = $matches[1][$i];
			if ( !isset( $roles[$group] ) ) {
				$roles[$group] = [];
			}
			$role = $matches[2][$i];
			$granted = $matches[3][$i] === 'true';
			$roles[$group][$role] = $granted;
		}
		return $roles;
	}

	/**
	 * @param string $source
	 *
	 * @return array
	 */
	private function parseNamespaceLockdown( string $source ) {
		$regexes = [
			'/\$GLOBALS\[\'bsgNamespaceRolesLockdown\'\]\[(.*?)\]\[(.*?)\]\s=\s\[(.*?)\];/',
			'/\$GLOBALS\[\'bsgNamespaceRolesLockdown\'\]\[(.*?)\]\[(.*?)\]\s=\sarray\((.*?)\);/'
		];
		$lockdown = [];
		foreach ( $regexes as $regex ) {
			$lockdownRound = $this->parseNamespaceLockdownFromRegex( $regex, $source );
			foreach ( $lockdownRound as $ns => $data ) {
				if ( !isset( $lockdown[$ns] ) ) {
					$lockdown[$ns] = [];
				}
				$lockdown[$ns] = array_merge( $lockdown[$ns], $data );
			}
		}
		return $lockdown;
	}

	/**
	 * @param string $regex
	 * @param string $source
	 *
	 * @return array
	 */
	private function parseNamespaceLockdownFromRegex( string $regex, string $source ) {
		$lockdown = [];
		$matches = [];
		$hasMatches = preg_match_all( $regex, $source, $matches );
		if ( !$hasMatches ) {
			return $lockdown;
		}
		foreach ( $matches[1] as $i => $namespace ) {
			$namespace = trim( $namespace );
			if ( is_numeric( $namespace ) ) {
				$namespace = (int)$namespace;
			} elseif ( strpos( $namespace, 'NS_' ) === 0 ) {
				$nsConst = $namespace;
				$namespace = $this->getNsIndex( $nsConst );
				if ( $namespace === null ) {
					$this->output( 'Warning: Cannot find ID for namespace ' . $nsConst . ". Skipping.\n" );
					continue;
				}
			} else {
				// Don't know what it is
				continue;
			}

			if ( !isset( $lockdown[$namespace] ) ) {
				$lockdown[$namespace] = [];
			}
			$role = trim( $matches[2][$i], '\'" ' );
			$groups = explode( ',', $matches[3][$i] );
			$groups = array_map( static function ( $group ) {
				return trim( $group, '\'" ' );
			}, $groups );
			$lockdown[$namespace][$role] = $groups;
		}

		return $lockdown;
	}

	/**
	 * @param string $namespace
	 *
	 * @return mixed|null
	 */
	private function getNsIndex( $namespace ) {
		if ( defined( $namespace ) ) {
			return constant( $namespace );
		}
		return null;
	}
}

$maintClass = MigratePmSettings::class;
require_once RUN_MAINTENANCE_IF_MAIN;
