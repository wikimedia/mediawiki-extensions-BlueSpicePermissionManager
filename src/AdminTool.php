<?php

namespace BlueSpice\PermissionManager;

use BlueSpice\IAdminTool;

class AdminTool implements IAdminTool {

	public function getURL() {
		$tool = \SpecialPage::getTitleFor( 'PermissionManager' );
		return $tool->getLocalURL();
	}

	public function getDescription() {
		return wfMessage( 'bs-permissionmanager-desc' );
	}

	public function getName() {
		return wfMessage( 'bs-permissionmanager-label' );
	}

	public function getClasses() {
		$classes = array(
			'bs-icon-key'
		);

		return $classes;
	}

	public function getDataAttributes() {
		return [];
	}

	public function getPermissions() {
		$permissions = array(
			'permissionmanager-viewspecialpage'
		);
		return $permissions;
	}

}