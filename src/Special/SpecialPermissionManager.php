<?php

namespace BlueSpice\PermissionManager\Special;

use BlueSpice\PermissionManager\Helper;

class SpecialPermissionManager extends \BlueSpice\SpecialPage {

	protected $groups = [];

	public function __construct() {
		parent::__construct( 'PermissionManager', 'permissionmanager-viewspecialpage' );
	}

	public function execute( $param ) {
		parent::execute( $param );

		$this->getOutput()->addModuleStyles(
			'ext.bluespice.permissionManager.styles'
		);
		$this->getOutput()->addModules( 'ext.bluespice.permissionManager' );

		$helper = Helper::getInstance();
		$groups = $helper->getGroups();

		$rolesAndPermissions = \BlueSpice\PermissionManager\Extension::getRoles();
		$rolesAndHints = $helper->formatPermissionsToHint( $rolesAndPermissions );

		$groupRoles = \BlueSpice\PermissionManager\Extension::getGroupRoles();

		$jsVars = array(
			'bsPermissionManagerGroupsTree' => $groups,
			'bsPermissionManagerRoles' => $rolesAndHints,
			'bsPermissionManagerNamespaces' => $helper->buildNamespaceMetadata(),
			'bsPermissionManagerGroupRoles' => $groupRoles,
			'bsPermissionManagerRoleLockdown' => $helper->getNamespaceRolesLockdown()
		);

		$this->getOutput()->addJsConfigVars( $jsVars );

		$this->getOutput()->addHTML( '<div id="panelPermissionManager"  class="bs-manager-container" style="height: 800px"></div>' );
	}
}

