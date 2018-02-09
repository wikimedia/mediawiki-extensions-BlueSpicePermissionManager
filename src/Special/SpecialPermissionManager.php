<?php

namespace BlueSpice\PermissionManager\Special;

use BlueSpice\PermissionManager\PermissionManager;
use BlueSpice\PermissionManager\Helper;

class SpecialPermissionManager extends \SpecialPage {

	protected $groups = [];

	public function __construct() {
		parent::__construct( 'PermissionManager', 'permissionmanager-viewspecialpage' );
	}

	public function execute( $param ) {
		parent::execute( $param );
		$this->getOutput()->addModules( 'ext.bluespice.permissionManager' );

		$helper = Helper::getInstance();
		$groups = $helper->getGroups();

		$rolesAndPermissions = PermissionManager::getRoles();
		$rolesAndHints = $helper->formatPermissionsToHint( $rolesAndPermissions );

		$groupRoles = PermissionManager::getGroupRoles();

		$jsVars = array(
			'bsPermissionManagerGroupsTree' => $groups,
			'bsPermissionManagerRoles' => $rolesAndHints,
			'bsPermissionManagerNamespaces' => $helper->buildNamespaceMetadata(),
			'bsPermissionManagerGroupRoles' => $groupRoles,
			'bsPermissionManagerRoleLockdown' => $helper->getNamespaceRolesLockdown()
		);

		$this->getOutput()->addJsConfigVars( $jsVars );

		$this->getOutput()->addHTML( '<div id="panelPermissionManager"  class="bs-manager-container" style="height: 500px"></div>' );
	}
}

