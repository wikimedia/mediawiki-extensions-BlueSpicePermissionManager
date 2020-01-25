<?php

namespace BlueSpice\PermissionManager\Special;

use BlueSpice\PermissionManager\Extension as PermissionManager;
use BlueSpice\PermissionManager\Helper;
use BlueSpice\Special\ManagerBase;

class SpecialPermissionManager extends ManagerBase {

	/**
	 *
	 * @var array
	 */
	protected $groups = [];

	public function __construct() {
		parent::__construct( 'PermissionManager', 'permissionmanager-viewspecialpage' );
	}

	/**
	 *
	 * @return string
	 */
	protected function getId() {
		return "panelPermissionManager";
	}

	/**
	 *
	 * @return array
	 */
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
	 *
	 * @return array
	 */
	protected function getAttributes() {
		return [
			"style" => "height: 800px"
		];
	}
}
