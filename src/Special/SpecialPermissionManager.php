<?php

namespace BlueSpice\PermissionManager\Special;

use BlueSpice\PermissionManager\Helper;
use BlueSpice\Special\ManagerBase;
use BlueSpice\PermissionManager\Extension as PermissionManager;
use BsGroupHelper;

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
		$groupTree = $helper->getGroups();
		$availableGroups = array_values(
			BsGroupHelper::getAvailableGroups(
				[ 'blacklist' => $this->getConfig()->get( 'ImplicitGroups' ) ]
			)
		);
		$rolesAndPermissions = PermissionManager::getRoles();
		$rolesAndHints = $helper->formatPermissionsToHint( $rolesAndPermissions );

		$groupRoles = PermissionManager::getGroupRoles();

		return [
			'bsPermissionManagerGroupsTree' => $groupTree,
			'bsPermissionManagerRoles' => $rolesAndHints,
			'bsPermissionManagerNamespaces' => $helper->buildNamespaceMetadata(),
			'bsPermissionManagerGroupRoles' => $groupRoles,
			'bsPermissionManagerRoleLockdown' => $helper->getNamespaceRolesLockdown(),
			'bsPermissionManagerRoleDependencyTree' => $helper->getRoleDependencyTree(),
			'bsPermissionManagerAvailableGroups' => $availableGroups,
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
