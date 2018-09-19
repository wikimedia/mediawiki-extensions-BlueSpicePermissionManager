<?php

/**
 * PermissionManager Extension for BlueSpice
 *
 * Administration interface for managing permissions.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * This file is part of BlueSpice MediaWiki
 * For further information visit http://www.bluespice.com
 *
 * @author     Dejan Savuljesku <savuljesku@hallowelt.com>
 * @package    BlueSpice_Extensions
 * @subpackage PermissionManager
 * @copyright  Copyright (C) 2018 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v3
 * @filesource
 */

namespace BlueSpice\PermissionManager;
use BlueSpice;
use BlueSpice\PermissionManager\RoleMatrixDiff;

class Extension extends \BlueSpice\Extension{
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
			'BlueSpice\PermissionManager\Extension::run'
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

	public static function getRolePermissions( $role, $includeDesc = false ) {
		$role = self::$roleManager->getRole( $role );
		if( $role instanceof \BlueSpice\Permission\Role\IRole === false ) {
			return [];
		}

		$permissions = $role->getPermissions();
		if( !$includeDesc ) {
			return $permissions;
		}

		$permissionsAndDescs = [];
		foreach( $permissions as $permission ) {
			$permissionsAndDescs[ $permission ] =
				wfMessage( "right-$permission" )->plain();
		}
		return $permissionsAndDescs;
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

		$roleMatrixDiff = new RoleMatrixDiff( $config, $groupRoles, $roleLockdown );
		$globalDiff = $roleMatrixDiff->getGlobalDiff();
		$nsDiff = $roleMatrixDiff->getNsDiff();

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
			self::doLog( $globalDiff, $nsDiff );
			return array( 'success' => true );
		} else {
			return array(
					'success' => false,
					'msg' => wfMessage( 'bs-permissionmanager-write-config-file-error', $configFile )
			);
		}
	}

	protected static function doLog( $globalDiff, $nsDiff ) {
		foreach( $globalDiff as $group => $roles ) {
			$addedRoles = [];
			$removedRoles = [];
			foreach( $roles as $role => $added ) {
				if( $added ) {
					$addedRoles[] = $role;
				} else {
					$removedRoles[] = $role;
				}
			}
			if( !empty( $addedRoles ) ) {
				self::insertLog( 'global-add', [
					'4::diffGroup' => $group,
					'5::diffRoles' => implode( ',', $addedRoles ),
					'6::roleCount' => count( $addedRoles )
				] );
			}
			if( !empty( $removedRoles ) ) {
				self::insertLog( 'global-remove', [
					'4::diffGroup' => $group,
					'5::diffRoles' => implode( ',', $removedRoles ),
					'6::roleCount' => count( $removedRoles )
				] );
			}
		}

		foreach( $nsDiff as $group => $namespaces ) {
			foreach( $namespaces as $ns => $roles ) {
				$nsCanonical = \MWNamespace::getCanonicalName( $ns );
				if( $ns === NS_MAIN ) {
					$nsCanonical = wfMessage( 'bs-ns_main' )->plain();
				}
				$addedRoles = [];
				$removedRoles = [];
				foreach( $roles as $role => $added ) {
					if( $added ) {
						$addedRoles[] = $role;
					} else {
						$removedRoles[] = $role;
					}
				}
				if( !empty( $addedRoles ) ) {
					self::insertLog( 'ns-add', [
						'4::diffGroup' => $group,
						'5::diffRoles' => implode( ',', $addedRoles ),
						'6::roleCount' => count( $addedRoles ),
						'7::ns' => $nsCanonical
					] );
				}
				if( !empty( $removedRoles ) ) {
					self::insertLog( 'ns-remove', [
						'4::diffGroup' => $group,
						'5::diffRoles' => implode( ',', $removedRoles ),
						'6::roleCount' => count( $removedRoles ),
						'7::ns' => $nsCanonical
					] );
				}
			}
		}
	}

	protected static function insertLog( $type, $params ) {
		$targetTitle = \SpecialPage::getTitleFor( 'PermissionManager' );
		$user = \RequestContext::getMain()->getUser();

		$logger = new \ManualLogEntry( 'bs-permission-manager', $type );
		$logger->setPerformer( $user );
		$logger->setTarget( $targetTitle );
		$logger->setParameters( $params );
		$logger->insert();
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

