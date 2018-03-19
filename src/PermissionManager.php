<?php

namespace BlueSpice\PermissionManager;
use BlueSpice;

class PermissionManager {
	/**
	 * Instance of Manager class that handles
	 * all role-related operations
	 *
	 * @var BlueSpice\Permission\Manager
	 */
	protected static $roleManager;
	/**
	 * Instance of PermissionRegistry class
	 * in charge of handling individual permissions
	 *
	 * @var type
	 */
	protected static $permissionRegistry;

	public static function onCallback() {
		$GLOBALS[ 'bsgConfigFiles' ][ 'PermissionManager' ] = BSCONFIGDIR . '/pm-settings.php';

		array_unshift(
			$GLOBALS['wgExtensionFunctions'],
			'BlueSpice\PermissionManager\PermissionManager::run'
		);
	}

	public static function run() {
		self::$permissionRegistry = \MediaWiki\MediaWikiServices::getInstance()->getService(
			'BSPermissionRegistry'
		);
		self::$roleManager = \MediaWiki\MediaWikiServices::getInstance()->getService(
			'BSRoleManager'
		);

		//Implicitly enable role system
		if( self::$roleManager->isRoleSystemEnabled() == false ) {
			self::$roleManager->enableRoleSystem();
		}
	}

	public static function getRoles() {
		$roleNames = self::$roleManager->getRoleNamesAndPermissions();

		return $roleNames;
	}

	public static function getGroupRoles () {
		return self::$roleManager->getGroupRoles();
	}

	public static function saveRoles( $data ) {
		if ( !isset( $data ) || !isset( $data->groupRoles ) || !isset( $data->roleLockdown ) ) {
			return false;
		}

		$groupRoles = ( array ) $data->groupRoles;
		$roleLockdown = ( array ) $data->roleLockdown;

		$status = \Hooks::run( 'BsPermissionManager::beforeSaveRoles', array( &$groupRoles, &$roleLockdown ) );

		if ( !$status ) {
			return false;
		}

		$statusWritePMSettings = self::writeGroupSettings( $groupRoles, $roleLockdown );
		return $statusWritePMSettings;
	}

	protected static function writeGroupSettings( $groupRoles, $roleLockdown ) {
		$config = \MediaWiki\MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'bsg' );
		$configFile = $config->get( 'ConfigFiles' )[ 'PermissionManager' ];

		if ( wfReadOnly() ) {
			return array(
					'success' => false,
					'msg' => wfMessage( 'bs-readonly', wfReadOnlyReason() )->plain()
			);
		}
		if ( \BsCore::checkAccessAdmission( 'wikiadmin' ) === false ) {
			return true;
		}

		self::backupExistingSettings();

		$saveContent = "<?php\n";
		foreach( $groupRoles as $group => $roleArray ) {
			foreach ( $roleArray as $role => $value ) {
				$saveContent .= "\$GLOBALS['bsgGroupRoles']['{$group}']['{$role}'] = " . ( $value ? 'true' : 'false' ) . ";\n";
			}
		}

		foreach( $roleLockdown as $nsId => $roles ) {
			$nsCanonicalName = \MWNamespace::getCanonicalName( $nsId );
			if( $nsId == NS_MAIN ) {
				$nsCanonicalName = 'MAIN';
			}

			$nsConstant = "NS_" . strtoupper( $nsCanonicalName );
			if( !defined( $nsConstant ) ) {
				$nsConstant = $nsId;
			}

			$isReadLockdown = false;
			foreach( $roles as $roleName => $groups ) {
				if( empty( $groups ) ) {
					continue;
				}
				$saveContent .= "\$GLOBALS['bsgNamespaceRolesLockdown'][ $nsConstant ][ '$roleName' ]"
					. " = array(" . ( count( $groups ) ? "'" . implode( "','", $groups ) . "'" : '' ) . ");\n";
				$roleObject = self::$roleManager->getRole( $roleName );
				if( $roleObject == null ) {
					continue;
				}
				$permissions = $roleObject->getPermissions();
				if( in_array( 'read', $permissions ) ) {
					$isReadLockdown = true;
				}

			}
			if ( $isReadLockdown ) {
				$saveContent .= "\$GLOBALS['wgNonincludableNamespaces'][] = $nsConstant;\n";
			}
		}

		$res = file_put_contents( $configFile, $saveContent );
		if ( $res ) {
			return array( 'success' => true );
		} else {
			return array(
					'success' => false,
					'msg' => wfMessage( 'bs-permissionmanager-write-config-file-error', $configFile )
			);
		}
	}

	/**
	 * creates a backup of the current pm-settings.php if it exists.
	 *
	 * @global string $bsgConfigFiles
	 */
	protected static function backupExistingSettings() {
		$config = \MediaWiki\MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'bsg' );
		$configFile = $config->get( 'ConfigFiles' )[ 'PermissionManager' ];

		if ( file_exists( $configFile ) ) {
			$timestamp = wfTimestampNow();
			$backupFilename = "pm-settings-backup-{$timestamp}.php";
			$backupFile = dirname( $configFile ) . "/{$backupFilename}";

			file_put_contents( $backupFile, file_get_contents( $configFile ) );
		}

		//remove old backup files if max number exceeded
		$arrConfigFiles = scandir( dirname( $configFile ) . "/", SCANDIR_SORT_ASCENDING );
		$arrBackupFiles = array_filter( $arrConfigFiles, function( $elem ) {
			return ( strpos( $elem, "pm-settings-backup-" ) !== FALSE ) ? true : false;
		} );
		
		//default limit to 5 backups, remove all backup files until "maxbackups" files left
		while ( count( $arrBackupFiles ) > $config->get( "PermissionManagerMaxBackups" ) ) {
			$oldBackupFile = dirname( $configFile ) . "/" . array_shift( $arrBackupFiles );
			unlink( $oldBackupFile );
		}
	}
}

