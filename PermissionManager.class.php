<?php

/**
 * PermissionManager extension for BlueSpice
 *
 * Provides information about an article for status bar.
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
 * @author     Sebastian Ulbricht <sebastian.ulbricht@gmx.de>
 * @author     Leonid Verhovskij <verhovskij@hallowelt.com>
 * @package    BlueSpicePermissionManager
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v3
 * @filesource
 */

/**
 * Class for managing all usergroup permissions
 * @package BlueSpicePermissionManager
 */
class PermissionManager extends BsExtensionMW {

	/**
	 * @var array
	 */
	public static $aSysopDefaultPermissions = array(
		'block' => true,
		'createaccount' => true,
		'delete' => true,
		'bigdelete' => true,
		'deletedhistory' => true,
		'deletedtext' => true,
		'undelete' => true,
		'editinterface' => true,
		'editusercss' => true,
		'edituserjs' => true,
		'import' => true,
		'importupload' => true,
		'move' => true,
		'move-subpages' => true,
		'move-rootuserpages' => true,
		'patrol' => true,
		'autopatrol' => true,
		'protect' => true,
		'editprotected' => true,
		'rollback' => true,
		'upload' => true,
		'reupload' => true,
		'reupload-shared' => true,
		'unwatchedpages' => true,
		'autoconfirmed' => true,
		'editsemiprotected' => true,
		'ipblock-exempt' => true,
		'blockemail' => true,
		'markbotedits' => true,
		'apihighlimits' => true,
		'browsearchive' => true,
		'noratelimit' => true,
		'movefile' => true,
		'unblockself' => true,
		'suppressredirect' => true,
		'wikiadmin' => true
	);
	/**
	 * @var array
	 */
	public static $aGroups = array();
	public static $aBuiltInGroups = array(
			'autoconfirmed', 'emailconfirmed', 'bot', 'sysop', 'bureaucrat', 'developer'
	);
	/**
	 * @var array
	 */
	public static $aInvisibleGroups = array( 'sysop' );

	/**
	 * Constructor of PermissionManager
	 */
	public function __construct() {
		wfProfileIn( 'BS::' . __METHOD__);
		WikiAdmin::registerModule( 'PermissionManager', [
			'image' => '/extensions/BlueSpiceExtensions/WikiAdmin/resources/images/bs-btn_rechteverwaltung_v1.png',
			'level' => 'wikiadmin',
			'message' => 'bs-permissionmanager-label',
			'iconCls' => 'bs-icon-key',
			'permissions' => [ 'permissionmanager-viewspecialpage' ],
		]);
		wfProfileOut( 'BS::' . __METHOD__ );
	}

	protected function initExt() {
		$this->setHook( 'BSGroupManagerGroupNameChanged' );
		$this->setHook( 'BSGroupManagerGroupDeleted' );

		$this->mCore->registerPermission( 'permissionmanager-viewspecialpage', array( 'sysop' ), array( 'type' => 'global' ) );
	}

	/**
	 * Hook-Handler for Hook 'LoadExtensionSchemaUpdates'
	 * @param object $updater Updater
	 * @return boolean Always true
	 */
	public static function getSchemaUpdates( $updater ) {
		$updater->addExtensionTable(
				'bs_permission_templates',
				__DIR__ . '/' . 'db' . '/' . 'PermissionManager.sql'
		);

		return true;
	}

	public function onBSGroupManagerGroupNameChanged( $sGroup, $sNewGroup, &$result ) {
		global $wgGroupPermissions, $wgNamespacePermissionLockdown;
		$wgGroupPermissions[ $sNewGroup ] = $wgGroupPermissions[ $sGroup ];
		unset( $wgGroupPermissions[ $sGroup ] );
		foreach ( $wgNamespacePermissionLockdown as $iNs => $aPermissions ) {
			foreach ( $aPermissions as $sPermission => $aGroups ) {
				$iIndex = array_search( $sGroup, $aGroups );
				if ( $iIndex !== false ) {
					array_splice( $wgNamespacePermissionLockdown[ $iNs ][ $sPermission ], $iIndex, 1, array( $sNewGroup ) );
				}
			}
		}
		$result = PermissionManager::writeGroupSettings( $wgGroupPermissions, $wgNamespacePermissionLockdown );
		return true;
	}

	public function onBSGroupManagerGroupDeleted( $sGroup, &$result ) {
		global $wgGroupPermissions, $wgNamespacePermissionLockdown;
		unset( $wgGroupPermissions[ $sGroup ] );

		foreach ( $wgNamespacePermissionLockdown as $iNS => $aPermissions ) {
			foreach ( $aPermissions as $sPermission => $aGroups ) {
				$iIndex = array_search( $sGroup, $aGroups );
				if ( $iIndex !== false ) {
					if ( count( $aGroups ) == 1 ) {
						unset( $wgNamespacePermissionLockdown[ $iNS ][ $sPermission ] );
					} else {
						array_splice( $wgNamespacePermissionLockdown[ $iNS ][ $sPermission ], $iIndex, 1 );
					}
				}
			}
		}

		$result = PermissionManager::writeGroupSettings( $wgGroupPermissions, $wgNamespacePermissionLockdown );
		return true;
	}

	public static function buildNamespaceMetadata() {
		global $wgLang;

		$aNamespaces = $wgLang->getNamespaces();
		ksort( $aNamespaces );

		$aMetadata = array();

		foreach ( $aNamespaces as $iNSId => $sLocalizedNSText ) {
			if ( $iNSId < 0 ) { //Filter pseudo namespaces
				continue;
			}

			$sNsText = str_replace( '_', ' ', $sLocalizedNSText );
			if ( $iNSId == NS_MAIN ) {
				$sNsText = wfMessage( 'bs-ns_main' )->text();
			}

			$aMetadata[] = array(
					'id' => $iNSId,
					'name' => $sNsText,
					'hideable' => $iNSId !== NS_MAIN
			);
		}

		return $aMetadata;
	}

	/**
	 * This is to check if a right is global or local. This is stored in the
	 * $aMetadata to creates categorys in the Permission Manager form
	 * @global Array $bsgPermissionConfig stores various configurations for rights
	 * @return Array $aMetadata stores values needed in the permissionmanager getForm()
	 * i.e. if a right is global or local.
	 */
	public static function buildRightsMetadata() {
		global $bsgPermissionConfig;
		$aRights = User::getAllRights();
		$aMetadata = array();
		natsort( $aRights );
		if ( is_array( $aRights ) ) {
			foreach ( $aRights as $sRight ) {
				if ( !isset( $bsgPermissionConfig[ $sRight ] ) ) {
					$bsgPermissionConfig[ $sRight ] = array(
							'type' => 'namespace'
					);
				}
				$aConfig = $bsgPermissionConfig[ $sRight ];
				$bGlobalPermission = ( isset( $aConfig[ 'type' ] ) && $aConfig[ 'type' ] == 'global' ) ? true : false;
				$aMetadata[] = array(
					'hint' => wfMessage( 'right-' . $sRight )->plain(),
					'right' => $sRight,
					'type' => $bGlobalPermission ? 2 : 1,
					'typeHeader' => $bGlobalPermission
							? wfMessage( 'bs-permissionmanager-grouping-global' )->plain()
							: wfMessage( 'bs-permissionmanager-grouping-local' )->plain()
				);
			}
		}
		Hooks::run( 'BsPermissionManager::buildRightsMetadata', array( &$aMetadata ) );

		return $aMetadata;
	}

	public static function setTemplateData( $oTemplate ) {
		global $wgRequest;

		$dbw = wfGetDB( DB_MASTER );
		//return false if $oTemplate is empty
		if ( $oTemplate == null ) {
			$aResult = array(
				'success' => false,
				'msg' => ''
			);
			return $aResult;
		}

		$iId = ( int ) $oTemplate->id;
		$sName = $dbw->strencode( $oTemplate->text );
		$aPermissions = $oTemplate->ruleSet;
		$sDescription = $dbw->strencode( $oTemplate->description );

		if ( $iId == 0 ) {
			$bSaveResult = PermissionTemplates::addTemplate( $sName, $aPermissions, $sDescription );
			$iId = $dbw->insertId();
		} else {
			$bSaveResult = PermissionTemplates::editTemplate( $iId, $sName, $aPermissions, $sDescription );
		}
		$aResult = array(
				'success' => false,
				'msg' => $bSaveResult,
				'id' => $iId
		);

		if ( $bSaveResult ) {
			$aResult[ 'success' ] = true;
		} else {
			$aResult[ 'msg' ] = wfMessage( 'bs-permissionmanager-msgtpled-savefailure' )->plain();
		}

		return $aResult;
	}

	public static function deleteTemplate( $iId = 0 ) {

		if ( $iId ) {
			$bDeleteResult = PermissionTemplates::removeTemplate( $iId );
		} else {
			$bDeleteResult = false;
		}
		$aResult = array(
				'success' => false,
				'msg' => ''
		);

		if ( $bDeleteResult ) {
			$aResult[ 'success' ] = true;
		} else {
			$aResult[ 'msg' ] = wfMessage( 'bs-permissionmanager-msgtpled-deletefail' )->plain();
		}

		return $aResult;
	}

	/**
	 * @global WebRequest $wgRequest
	 * @return $aResult from RunHooks(BsPermissionManager::beforeSavePermissions)
	 * if not empty, otherwise $mStatusWritePMSettings if no error occurs, otherwise boolean:false
	 */
	public static function savePermissions( $data ) {

		if ( !isset( $data ) || !isset( $data->groupPermission ) || !isset( $data->permissionLockdown ) ) {
			return false;
		}

		$aGroupPermissions = ( array ) $data->groupPermission;
		$aLockdown = ( array ) $data->permissionLockdown;
		$aResult = array();
		$mStatus = Hooks::run( 'BsPermissionManager::beforeSavePermissions', array( &$aLockdown, &$aGroupPermissions, &$aResult ) );


		if ( !empty( $aResult ) ) {
			return $aResult;
		}

		if ( $mStatus === true ) {
			$mStatus = self::preventPermissionLockout( $aGroupPermissions );
		}

		if ( $mStatus === true ) {
			$mStatusWritePMSettings = self::writeGroupSettings( $aGroupPermissions, $aLockdown );
			return $mStatusWritePMSettings;
		}

		return $mStatus;
	}

	public static function getPermissionArray( $group = "", $timestamp = "" ) {
		global $wgImplicitGroups, $wgGroupPermissions, $wgNamespacePermissionLockdown;

		// Temporarily stash the original global settings, as PermissionManager
		// only deals with the subset of permissions it has control over (aka the
		// permissions which are stored in pm-settings.php. As this is a nested
		// array, we need a deep clone. An easy way to do this is to serialize and
		// then deserialize.
		$tmpImplicitGroups = unserialize( serialize( $wgImplicitGroups ) );
		$tmpGroupPermissions = unserialize( serialize( $wgGroupPermissions ) );
		$tmpNamespacePermissionLockdown = unserialize( serialize( $wgNamespacePermissionLockdown ) );

		//reset old data
		$wgImplicitGroups = array();
		$wgGroupPermissions = array();
		$wgNamespacePermissionLockdown = array();

		//load config from file
		if ( empty( $timestamp ) ) {
			include BSCONFIGDIR . DS . 'pm-settings.php';
		} else {
			//convert timestamp to date string and lookup backup file
			$strTime = wfTimestamp( TS_MW, $timestamp );
			$backupFilename = "pm-settings-backup-{$strTime}.php";
			include BSCONFIGDIR . DS . $backupFilename;
		}

		//set empty values in $wgGroupPermissions to 0 and remove not requested groups

		while ( list( $key, $wgGroupPermission ) = each( $wgGroupPermissions ) ) {
			if ( !empty( $group ) && $key != $group ) {
				unset( $wgGroupPermissions[ $key ] );
				continue;
			}
			foreach ( $wgGroupPermission as &$permission ) {
				if ( empty( $permission ) ) {
					$permission = 0;
				}
			}
		}

		$aGroups = array(
			'text' => '*',
			'builtin' => true,
			'implicit' => true,
			'expanded' => true,
			'children' => array(
				array(
					'text' => 'user',
					'builtin' => true,
					'implicit' => true,
					'expanded' => true,
					'children' => array()
				)
			)
		);

		$aExplicitGroups = BsGroupHelper::getAvailableGroups(
			array( 'blacklist' => $wgImplicitGroups )
		);

		sort( $aExplicitGroups );

		$aExplicitGroupNodes = array();
		foreach ( $aExplicitGroups as $sExplicitGroup ) {
			$aExplicitGroupNode = array(
				'text' => $sExplicitGroup,
				'leaf' => true
			);

			if ( in_array( $sExplicitGroup, PermissionManager::$aBuiltInGroups ) ) {
				$aExplicitGroupNode[ 'builtin' ] = true;
			}

			$aExplicitGroupNodes[] = $aExplicitGroupNode;
		}

		$aGroups[ 'children' ][ 0 ][ 'children' ] = $aExplicitGroupNodes;

		$aJsVars = array(
			'bsPermissionManagerGroupsTree' => $aGroups,
			'bsPermissionManagerNamespaces' => PermissionManager::buildNamespaceMetadata(),
			'bsPermissionManagerRights' => PermissionManager::buildRightsMetadata(),
			'bsPermissionManagerGroupPermissions' => $wgGroupPermissions,
			'bsPermissionManagerPermissionLockdown' => $wgNamespacePermissionLockdown,
			'bsPermissionManagerPermissionTemplates' => PermissionManager::getTemplateRules()
		);

		if ( empty( $aJsVars[ 'bsPermissionManagerPermissionTemplates' ] ) ) {
			unset( $aJsVars[ 'bsPermissionManagerPermissionTemplates' ] );
		}

		Hooks::run( 'BsPermissionManager::beforeLoadPermissions', array( &$aJsVars ) );

		//Make sure a new group without any explicit permissions is converted into an object!
		//Without any key => value it would be converted into an empty array.
		foreach ( $aJsVars[ 'bsPermissionManagerGroupPermissions' ] as $sGroup => $aPermissions ) {
			if ( !empty( $aPermissions ) ) {
				continue;
			}
			$aJsVars[ 'bsPermissionManagerGroupPermissions' ][ $sGroup ] = (object)array();
		}

		// Restore original global state.
		$wgImplicitGroups = $tmpImplicitGroups;
		$wgGroupPermissions = $tmpGroupPermissions;
		$wgNamespacePermissionLockdown = $tmpNamespacePermissionLockdown;
		BsGroupHelper::getAvailableGroups( ['reload' => true] );

		return $aJsVars;
	}

	/**
	 * Prevents that the wiki gets accidentally inaccessible for all users.
	 * Some of the rights which are pre-set in the $bsgPermissionConfig have
	 * a the flag "preventLockout" set to true. This makes it impossible to
	 * save the permission settings if not at least one group has these rights enabled.
	 *
	 * @param array $aGroupPermissions
	 * @return bool|String
	 */
	protected static function preventPermissionLockout( &$aGroupPermissions ) {
		global $bsgPermissionConfig;

		$aRights = User::getAllRights();
		if ( !is_array( $aRights ) ) {
			return false;
		}

		foreach ( $aRights as $sRight ) {
			if ( isset( $bsgPermissionConfig[ $sRight ][ 'preventLockout' ] ) ) {
				$bIsSet = false;
				if ( is_array( $aGroupPermissions ) ) {
					foreach ( $aGroupPermissions as $sGroupName => $aDataset ) {
						$aDataset = (array)$aDataset;
						if ( isset( $aDataset[ $sRight ] ) && $aDataset[ $sRight ] ) {
							$bIsSet = true;
							continue 2;
						}
					}
					if ( !$bIsSet ) {
						return Message::newFromKey( 'bs-permissionmanager-error-lockout' )
								->params( $sRight )
								->plain();
					}
				}
			}
		}

		return true;
	}

	public static function getTemplateRules() {
		$aTemplates = PermissionTemplates::getAll();
		$aOutput = array();

		/* @var $oTemplate PermissionTemplates */
		foreach ( $aTemplates as $oTemplate ) {
			$aOutput[] = array(
					'id' => $oTemplate->getId(),
					'text' => $oTemplate->getName(),
					'leaf' => true,
					'description' => $oTemplate->getDescription(),
					'ruleSet' => $oTemplate->getPermissions()
			);
		}

		return $aOutput;
	}

	protected static function writeGroupSettings( $aGroupPermissions, $aNamespacePermissionLockdown ) {
		global $bsgConfigFiles, $wgGroupPermissions, $wgNamespacePermissionLockdown;

		if ( wfReadOnly() ) {
			global $wgReadOnly;
			return array(
					'success' => false,
					'msg' => wfMessage( 'bs-readonly', $wgReadOnly )->plain()
			);
		}
		if ( BsCore::checkAccessAdmission( 'wikiadmin' ) === false )
			return true;

		Hooks::run( 'BsNamespacemanageOnSavePermission', array( &$aNamespacePermissionLockdown, &$aGroupPermissions ) );
		Hooks::run( 'BsPermissionManager::writeGroupSettings', array( &$aNamespacePermissionLockdown, &$aGroupPermissions ) );

		self::backupExistingSettings();

		// we save all groups which settings changed in this array
		$aDiffGroups = array();

		$sSaveContent = "<?php\n";
		foreach ( $aGroupPermissions as $sGroup => $aPermissions ) {
			foreach ( $aPermissions as $sPermission => $bValue ) {
				$sSaveContent .= "\$GLOBALS['wgGroupPermissions']['{$sGroup}']['{$sPermission}'] = " . ( $bValue ? 'true' : 'false' ) . ";\n";
				// check if settings for the given group changed
				if ( !isset( $wgGroupPermissions[ $sGroup ] )
						|| !isset( $wgGroupPermissions[ $sGroup ][ $sPermission ] )
						|| $wgGroupPermissions[ $sGroup ][ $sPermission ] != $bValue ) {
					$aDiffGroups[ $sGroup ] = true;
				}
			}
		}

		if ( is_array( $aNamespacePermissionLockdown ) ) {
			foreach ( $aNamespacePermissionLockdown as $iNS => $aPermissions ) {
				$isReadLockdown = false;
				$sNsCanonicalName = MWNamespace::getCanonicalName( $iNS );
				if ( $iNS == NS_MAIN ) {
					$sNsCanonicalName = 'MAIN';
				}
				$sNsConstant = 'NS_' . strtoupper( $sNsCanonicalName );
				//$sNsCanonicalName does not always match the constant name.
				//Fallback to NS index or this will throw a million notices
				//on every page load.
				if( !defined( $sNsConstant ) ) {
					$sNsConstant = $iNS;
				}
				foreach ( $aPermissions as $sPermission => $aGroups ) {
					if ( empty( $aGroups ) ) {
						continue;
					}
					$sSaveContent .= "\$GLOBALS['wgNamespacePermissionLockdown'][$sNsConstant]['$sPermission']"
						. " = array(" . ( count( $aGroups ) ? "'" . implode( "','", $aGroups ) . "'" : '' ) . ");\n";
					if ( $sPermission == 'read' ) {
						$isReadLockdown = true;
					}
					// check if settings for any group changed
					if ( isset( $wgNamespacePermissionLockdown[ $sNsConstant ] )
						&& isset( $wgNamespacePermissionLockdown[ $sNsConstant ][ $sPermission ] )
					) {
						$aLocalDiffGroups = array_diff( $aGroups, $wgNamespacePermissionLockdown[ $sNsConstant ][ $sPermission ] );
						foreach ( $aLocalDiffGroups as $sDiffGroup ) {
							$aDiffGroups[ $sDiffGroup ] = true;
						}
					}
				}
				if ( $isReadLockdown ) {
					$sSaveContent .= "\$GLOBALS['wgNonincludableNamespaces'][] = $sNsConstant;\n";
				}
			}
		}

		$res = file_put_contents( $bsgConfigFiles[ 'PermissionManager' ], $sSaveContent );
		if ( $res ) {
			// Create a log entry for any group which permissions changed
			$oTitle = SpecialPage::getTitleFor( 'WikiAdmin' );
			$oUser = RequestContext::getMain()->getUser();

			foreach ( $aDiffGroups as $sDiffGroup => $bFlag ) {
				if ( $bFlag ) {
					$oLogger = new ManualLogEntry( 'bs-permission-manager', 'modify' );
					$oLogger->setPerformer( $oUser );
					$oLogger->setTarget( $oTitle );
					$oLogger->setParameters( array(
							'4::diffGroup' => $sDiffGroup
					) );
					$oLogger->insert();
				}
			}
			return array( 'success' => true );
		} else {
			return array(
					'success' => false,
					// TODO SU (04.07.11 12:06): i18n
					'msg' => 'Not able to create or write "' . $bsgConfigFiles[ 'PermissionManager' ] . '".'
			);
		}
	}

	/**
	 * creates a backup of the current pm-settings.php if it exists.
	 *
	 * @global string $bsgConfigFiles
	 */
	protected static function backupExistingSettings() {
		global $bsgConfigFiles;

		if ( file_exists( $bsgConfigFiles[ 'PermissionManager' ] ) ) {
			$timestamp = wfTimestampNow();
			$backupFilename = "pm-settings-backup-{$timestamp}.php";
			$backupFile = dirname( $bsgConfigFiles[ 'PermissionManager' ] ) . "/{$backupFilename}";

			file_put_contents( $backupFile, file_get_contents( $bsgConfigFiles[ 'PermissionManager' ] ) );
		}

		//remove old backup files if max number exceeded
		$arrConfigFiles = scandir( dirname( $bsgConfigFiles[ 'PermissionManager' ] ) . "/", SCANDIR_SORT_ASCENDING );
		$arrBackupFiles = array_filter( $arrConfigFiles, function( $elem ) {
			return ( strpos( $elem, "pm-settings-backup-" ) !== FALSE ) ? true : false;
		} );

		$config = \MediaWiki\MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'bsg' );
		//default limit to 5 backups, remove all backup files until "maxbackups" files left
		while ( count( $arrBackupFiles ) > $config->get( "PermissionManagerMaxBackups" ) ) {
			$oldBackupFile = dirname( $bsgConfigFiles[ 'PermissionManager' ] ) . "/" . array_shift( $arrBackupFiles );
			unlink( $oldBackupFile );
		}
	}

	/**
	 * UnitTestsList allows registration of additional test suites to execute
	 * under PHPUnit. Extensions can append paths to files to the $paths array,
	 * and since MediaWiki 1.24, can specify paths to directories, which will
	 * be scanned recursively for any test case files with the suffix "Test.php".
	 * @param array $paths
	 */
	public static function onUnitTestsList( array &$paths ) {
		$paths[] = __DIR__ . '/tests/phpunit/';
		return true;
	}

}
