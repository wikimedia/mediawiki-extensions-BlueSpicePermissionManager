<?php

namespace BlueSpice\PermissionManager;

use BlueSpice\IAdminTool;
use MediaWiki\Message\Message;
use MediaWiki\SpecialPage\SpecialPage;

class AdminTool implements IAdminTool {

	/**
	 *
	 * @return string
	 */
	public function getURL() {
		$tool = SpecialPage::getTitleFor( 'PermissionManager' );
		return $tool->getLocalURL();
	}

	/**
	 *
	 * @return Message
	 */
	public function getDescription() {
		return wfMessage( 'bs-permissionmanager-desc' );
	}

	/**
	 *
	 * @return Message
	 */
	public function getName() {
		return wfMessage( 'bs-permissionmanager-label' );
	}

	/**
	 *
	 * @return string[]
	 */
	public function getClasses() {
		$classes = [
			'bs-icon-key'
		];

		return $classes;
	}

	/**
	 *
	 * @return array
	 */
	public function getDataAttributes() {
		return [];
	}

	/**
	 *
	 * @return string[]
	 */
	public function getPermissions() {
		$permissions = [
			'permissionmanager-viewspecialpage'
		];
		return $permissions;
	}

}
