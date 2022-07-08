<?php

namespace BlueSpice\PermissionManager\Special;

use BlueSpice\LoadPlaceholderRegistry;
use BlueSpice\PermissionManager\PermissionManager;
use BlueSpice\PermissionManager\Preset\CustomPreset;
use BlueSpice\Utility\GroupHelper;
use Html;
use MediaWiki\MediaWikiServices;
use SpecialPage;

class SpecialPermissionManager extends SpecialPage {
	/** @var PermissionManager */
	protected $permissionManager;

	/**
	 *
	 * @var array
	 */
	protected $groups = [];

	public function __construct() {
		parent::__construct( 'PermissionManager', 'permissionmanager-viewspecialpage' );

		$this->permissionManager = MediaWikiServices::getInstance()->getService(
			'BlueSpicePermissionManager'
		);
	}

	/**
	 * @inheritDoc
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$this->getOutput()->addHTML(
			Html::element( 'div', [ 'id' => 'bs-permission-manager-preset-select' ] )
		);
		$this->getOutput()->addHTML(
			Html::openElement( 'div', [ 'id' => 'bs-permission-manager-custom-preset' ] ) .
			$this->getLoadPlaceholder() .
			Html::closeElement( 'div' )
		);

		$this->addJSVars();

		$this->getOutput()->addModules( 'ext.bluespice.permissionManager' );
	}

	/**
	 * Add required JS vars
	 * Vars for "custom" preset should be loaded over the remove config once its ready
	 */
	protected function addJSVars() {
		$availablePresets = $this->permissionManager->getAvailablePresets();
		$activePreset = $this->permissionManager->getActivePreset();
		$presetData = [];
		foreach ( $availablePresets as $presetName ) {
			$preset = $this->permissionManager->getPreset( $presetName );
			if ( $preset === null ) {
				continue;
			}
			$presetData[$preset->getId()] = [
				'id' => $preset->getId(),
				'label' => $preset->getLabel()->text(),
				'help' => $preset->getHelpMessage()->parse(),
				'icon' => $preset->getIcon(),
				'active' => $activePreset->getId() === $preset->getId(),
			];
		}

		$this->getOutput()->addJsConfigVars( 'bsPermissionManagerPresets', $presetData );

		$groups = $this->permissionManager->getGroups();

		$rolesAndPermissions = $this->permissionManager->getRoleManager()->getRoleNamesAndPermissions();
		$rolesAndHints = $this->permissionManager->formatPermissionsToHint( $rolesAndPermissions );

		/** @var GroupHelper $groupHelper */
		$groupHelper = MediaWikiServices::getInstance()->getService(
			'BSUtilityFactory'
		)->getGroupHelper();
		$availableGroups = $groupHelper->getAvailableGroups( [ 'filter' => [ 'explicit' ] ] );
		// Include "implicit" groups as well
		$availableGroups = array_merge( [ '*', 'user' ], $availableGroups );

		$groupRoles = $this->permissionManager->getRoleManager()->getGroupRoles();
		$nsLockdown = $this->permissionManager->getNamespaceRolesLockdown();
		if ( $this->permissionManager->getActivePreset()->getId() !== 'custom' ) {
			// If custom is already applied, pm-settings is already and available
			/** @var CustomPreset $customPreset */
			$customPreset = $this->permissionManager->getPreset( 'custom' );
			$roles = $customPreset->evaluateSettingsFile();
			if ( is_array( $roles ) ) {
				$groupRoles = $roles['bsgGroupRoles'] ?? [];
				$nsLockdown = $roles['bsgNamespaceRolesLockdown'] ?? [];
			}
			// In case $roles is not an array, parsing failed,
			// lets apply whatever is loaded in the role system
		}
		$this->getOutput()->addJsConfigVars( [
			'bsPermissionManagerGroupsTree' => $groups,
			'bsPermissionManagerRoles' => $rolesAndHints,
			'bsPermissionManagerNamespaces' => $this->permissionManager->buildNamespaceMetadata(),
			'bsPermissionManagerGroupRoles' => $groupRoles,
			'bsPermissionManagerRoleLockdown' => $nsLockdown,
			'bsPermissionManagerRoleDependencyTree' => $this->permissionManager->getRoleDependencyTree(),
			'bsPermissionManagerAvailableGroups' => $availableGroups,
		] );
	}

	/**
	 *
	 * @return string[]
	 */
	protected function getModules() {
		return [
			'ext.bluespice.permissionManager.styles',
			'ext.bluespice.permissionManager'
		];
	}

	/**
	 * @return string
	 */
	private function getLoadPlaceholder() {
		$registry = new LoadPlaceholderRegistry();
		return $registry->getParsedTemplate( 'CRUDGrid' );
	}
}
