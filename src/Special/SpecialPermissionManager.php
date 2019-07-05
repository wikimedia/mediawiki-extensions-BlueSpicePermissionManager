<?php

namespace BlueSpice\PermissionManager\Special;

use BlueSpice\PermissionManager\Helper;
use BlueSpice\Special\ManagerBase;
use BlueSpice\PermissionManager\Extension as PermissionManager;

class SpecialPermissionManager extends ManagerBase {

	protected $groups = [];

	public function __construct() {
		parent::__construct( 'PermissionManager', 'permissionmanager-viewspecialpage' );
	}

	protected function getId() {
		return "panelPermissionManager";
	}

	protected function getJSVars() {
		$helper = Helper::getInstance();
		$groups = $helper->getGroups();

		$rolesAndPermissions = PermissionManager::getRoles();
		$rolesAndHints = $helper->formatPermissionsToHint( $rolesAndPermissions );

		$groupRoles = PermissionManager::getGroupRoles();

		return [
			'bsPermissionManagerGroupsTree' => $groups,
			'bsPermissionManagerRoles' => $rolesAndHints,
			'bsPermissionManagerNamespaces' => $helper->buildNamespaceMetadata(),
			'bsPermissionManagerGroupRoles' => $groupRoles,
			'bsPermissionManagerRoleLockdown' => $helper->getNamespaceRolesLockdown(),
			'bsPermissionManagerRoleDependencyTree' => $helper->getRoleDependencyTree()
		];
	}

	protected function getModules() {
		return [
			'ext.bluespice.permissionManager.styles',
			'ext.bluespice.permissionManager'
		];
	}

	protected function getAttributes() {
		return [
			"style" => "height: 800px"
		];
	}
}
