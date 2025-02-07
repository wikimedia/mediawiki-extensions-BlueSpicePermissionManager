<?php

namespace BlueSpice\PermissionManager\Rest;

use BlueSpice\PermissionManager\PermissionManager as BSPermissionManager;
use BlueSpice\PermissionManager\Preset\CustomPreset;
use MediaWiki\Config\ConfigFactory;
use MediaWiki\Context\RequestContext;
use MediaWiki\Language\Language;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Title\NamespaceInfo;

class PermissionMatrix extends SimpleHandler {

	/** @var BSPermissionManager */
	private $bsPermissionManager;

	/** @var Language */
	private $contentLang;

	/** @var NamespaceInfo */
	private $nsInfo;

	/** @var ConfigFactory */
	private $configFactory;

	/**
	 * @param BSPermissionManager $bsPermissionManager
	 * @param Language $contentLang
	 * @param NamespaceInfo $nsInfo
	 * @param ConfigFactory $configFactory
	 */
	public function __construct(
		BSPermissionManager $bsPermissionManager, Language $contentLang,
		NamespaceInfo $nsInfo, ConfigFactory $configFactory
	) {
		$this->bsPermissionManager = $bsPermissionManager;
		$this->contentLang = $contentLang;
		$this->nsInfo = $nsInfo;
		$this->configFactory = $configFactory;
	}

	public function execute() {
		$this->assertUserCan();

		$rolesAndPermissions = $this->bsPermissionManager->getRoleManager()->getRoleNamesAndPermissions();
		$rolesAndHints = $this->formatPermissionsToHint( $rolesAndPermissions );

		$groupRoles = $this->bsPermissionManager->getRoleManager()->getGroupRoles();
		$nsLockdown = $this->bsPermissionManager->getNamespaceRolesLockdown();
		if ( $this->bsPermissionManager->getActivePreset()->getId() !== 'custom' ) {
			// If custom is already applied, pm-settings is already and available
			$customPreset = $this->bsPermissionManager->getPreset( 'custom' );
			if ( $customPreset instanceof CustomPreset ) {
				$roles = $customPreset->readOutSettings();
				if ( is_array( $roles ) ) {
					$groupRoles = $roles['bsgGroupRoles'] ?? [];
					$nsLockdown = $roles['bsgNamespaceRolesLockdown'] ?? [];
				}
				// In case $roles is not an array, parsing failed,
				// lets apply whatever is loaded in the role system
			}
		}

		return $this->getResponseFactory()->createJson( [
			'roles' => $rolesAndHints,
			'groupRoles' => $groupRoles,
			'nsLockdown' => $nsLockdown,
			'roleDependencyTree' => $this->bsPermissionManager->getRoleDependencyTree(),
			'namespaces' => $this->buildNamespaceMetadata(),
		] );
	}

	/**
	 * @return true
	 */
	public function needsReadAccess() {
		return true;
	}

	/**
	 * @return void
	 * @throws HttpException
	 */
	private function assertUserCan() {
		$user = RequestContext::getMain()->getUser();
		if ( !$user->isAllowed( 'wikiadmin' ) ) {
			throw new HttpException( 'Permission denied', 403 );
		}
	}

	/**
	 *
	 * @param array $rolesAndPermissions
	 * @return array
	 */
	private function formatPermissionsToHint( $rolesAndPermissions ) {
		$res = [];
		foreach ( $rolesAndPermissions as $roleAndPermissions ) {
			$permissionList = implode( ', ', $roleAndPermissions[ 'permissions' ] );
			$permissionCount = count( $roleAndPermissions[ 'permissions' ] );
			$hintText = wfMessage( 'bs-permissionmanager-hint', $permissionList, $permissionCount )->parse();
			$permissionListHtml = implode( array_map( static function ( $permission ) {
				return '<span class="bs-permission-manager-permission">' . $permission . '</span>';
			}, $roleAndPermissions[ 'permissions' ] ) );
			$res[] = [
				'role' => $roleAndPermissions[ 'role' ],
				'labelExists' => $roleAndPermissions['labelExists'],
				'label' => $roleAndPermissions['label'],
				'hint' => $hintText,
				'hintHtml' => wfMessage( 'bs-permissionmanager-hint', $permissionListHtml, $permissionCount )->parse(),
				'privilegeLevel' => $roleAndPermissions[ 'privilegeLevel' ]
			];
		}

		$privilegeColumn = array_column( $res, 'privilegeLevel' );
		$nameColumn = array_column( $res, 'role' );
		array_multisort( $privilegeColumn, SORT_ASC, $nameColumn, SORT_ASC, $res );

		return $res;
	}

	/**
	 *
	 * @return array
	 */
	private function buildNamespaceMetadata() {
		$namespaces = $this->contentLang->getNamespaces();

		uksort( $namespaces, function ( $a, $b ) {
			// Talk namespace to the bottom, content in front of non-talk non-content
			$aContent = $this->nsInfo->isContent( $a );
			$bContent = $this->nsInfo->isContent( $b );
			$aTalk = $this->nsInfo->isTalk( $a );
			$bTalk = $this->nsInfo->isTalk( $b );

			if ( $aContent && !$bContent ) {
				return -1;
			} elseif ( !$aContent && $bContent ) {
				return 1;
			} elseif ( !$aContent && !$aTalk && ( $bContent || $bTalk ) ) {
				return -1;
			} elseif ( !$bContent && !$bTalk && ( $aContent || $aTalk ) ) {
				return 1;
			} elseif ( $aTalk && !$bTalk ) {
				return 1;
			} elseif ( !$aTalk && $bTalk ) {
				return -1;
			} else {
				return $a - $b;
			}
		} );

		$metadata = [];

		$config = $this->configFactory->makeConfig( 'bsg' );
		$customNsOffset = $config->has( 'NamespaceManagerNsOffset' ) ?
			$config->get( 'NamespaceManagerNsOffset' ) : 3000;

		foreach ( $namespaces as $nsId => $localizedNSText ) {
			if ( $nsId < 0 ) {
				// Filter pseudo namespaces
				continue;
			}

			$nsText = str_replace( '_', ' ', $localizedNSText );
			if ( $nsId == NS_MAIN ) {
				$nsText = wfMessage( 'bs-ns_main' )->text();
			}
			$metadata[] = [
				'id' => $nsId,
				'name' => $nsText,
				'hideable' => $nsId !== NS_MAIN,
				'content' => $this->nsInfo->isContent( $nsId ),
				'talk' => $this->nsInfo->isTalk( $nsId ),
				'custom' => $nsId > $customNsOffset,
			];
		}

		return $metadata;
	}
}
