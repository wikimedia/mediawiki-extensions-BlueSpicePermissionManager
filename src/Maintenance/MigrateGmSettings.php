<?php

namespace BlueSpice\PermissionManager\Maintenance;

use Exception;
use MediaWiki\Maintenance\LoggedUpdateMaintenance;
use MediaWiki\MediaWikiServices;
use MWStake\MediaWiki\Component\DynamicConfig\DynamicConfigManager;

require_once dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/maintenance/Maintenance.php';

class MigrateGmSettings extends LoggedUpdateMaintenance {
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
		return 'group-manager-migrate-gm-settings';
	}

	/**
	 * @param DynamicConfigManager $configManager
	 *
	 * @return bool
	 * @throws Exception
	 */
	private function isMigrated( DynamicConfigManager $configManager ) {
		$config = $configManager->getConfigObject( 'bs-groupmanager-groups' );
		if ( !$config ) {
			throw new Exception( 'Dynamic config for GroupManager not found' );
		}
		$data = $configManager->retrieveRaw( $config );
		return $data !== null;
	}

	/**
	 * @return array|null
	 */
	private function parseOldSettings(): ?array {
		$path = BS_LEGACY_CONFIGDIR . '/gm-settings.php';
		if ( !file_exists( $path ) ) {
			$this->output( 'Old settings file not found. Nothing to do.' );
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
			$this->output( 'Could not read old settings file' );
			return null;
		}
		$additionalGroups = $this->parseAdditionalGroups( $source );
		return [ 'wgAdditionalGroups' => $additionalGroups ];
	}

	/**
	 * @param array $parsed
	 * @param DynamicConfigManager $configManager
	 *
	 * @return false
	 */
	private function storeSettings( array $parsed, DynamicConfigManager $configManager ): bool {
		$config = $configManager->getConfigObject( 'bs-groupmanager-groups' );
		return $configManager->storeConfig( $config, [], serialize( $parsed ) );
	}

	/**
	 * @param string $source
	 *
	 * @return array
	 */
	private function parseAdditionalGroups( string $source ) {
		$groups = [];
		$matches = [];
		$hasMatches = preg_match_all(
			'/\$GLOBALS\[\'wgAdditionalGroups\'\]\[\'(.*?)\'\]/', $source, $matches
		);
		if ( !$hasMatches ) {
			return $groups;
		}
		foreach ( $matches[1] as $i => $name ) {
			$groups[$name] = [];
		}
		return $groups;
	}
}

$maintClass = MigrateGmSettings::class;
require_once RUN_MAINTENANCE_IF_MAIN;
