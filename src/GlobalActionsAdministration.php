<?php

namespace BlueSpice\PermissionManager;

use MediaWiki\Message\Message;
use MediaWiki\SpecialPage\SpecialPage;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\RestrictedTextLink;

class GlobalActionsAdministration extends RestrictedTextLink {

	/**
	 *
	 */
	public function __construct() {
		parent::__construct( [] );
	}

	/**
	 *
	 * @return string
	 */
	public function getId(): string {
		return 'ga-bs-permissionmanager';
	}

	/**
	 *
	 * @return array
	 */
	public function getPermissions(): array {
		return [ 'wikiadmin' ];
	}

	/**
	 *
	 * @return string
	 */
	public function getHref(): string {
		$tool = SpecialPage::getTitleFor( 'PermissionManager' );
		return $tool->getLocalURL();
	}

	/**
	 *
	 * @return Message
	 */
	public function getText(): Message {
		return Message::newFromKey( 'bs-permissionmanager-text' );
	}

	/**
	 *
	 * @return Message
	 */
	public function getTitle(): Message {
		return Message::newFromKey( 'bs-permissionmanager-desc' );
	}

	/**
	 *
	 * @return Message
	 */
	public function getAriaLabel(): Message {
		return Message::newFromKey( 'bs-permissionmanager-text' );
	}
}
