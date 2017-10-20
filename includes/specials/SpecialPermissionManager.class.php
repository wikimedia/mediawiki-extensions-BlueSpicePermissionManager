<?php

class SpecialPermissionManager extends BsSpecialPage {

	public function __construct() {
		parent::__construct( 'PermissionManager', 'permissionmanager-viewspecialpage' );

	}

	/**
	 *
	 * @global OutputPage $this->getOutput()
	 * @param type $sParameter
	 * @return type
	 */
	public function execute( $sParameter ) {
		parent::execute( $sParameter );
		global $wgImplicitGroups, $wgGroupPermissions, $wgNamespacePermissionLockdown;

		$this->getOutput()->addModules( 'ext.bluespice.permissionManager' );

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

		Hooks::run( 'BsPermissionManager::beforeLoadPermissions', array( &$aJsVars ) );

		//Make sure a new group without any explicit permissions is converted into an object!
		//Without any key => value it would be converted into an empty array.
		foreach ( $aJsVars[ 'bsPermissionManagerGroupPermissions' ] as $sGroup => $aPermissions ) {
			if ( !empty( $aPermissions ) )
				continue;
			$aJsVars[ 'bsPermissionManagerGroupPermissions' ][ $sGroup ] = ( object ) array();
		}

		$this->getOutput()->addJsConfigVars( $aJsVars );

		$this->getOutput()->addHTML( '<div id="panelPermissionManager"  class="bs-manager-container" style="height: 500px"></div>' );
	}

	protected function getGroupName() {
		return 'bluespice';
	}

}
