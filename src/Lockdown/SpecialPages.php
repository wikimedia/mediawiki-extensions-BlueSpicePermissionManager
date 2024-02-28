<?php

namespace BlueSpice\PermissionManager\Lockdown;

use BlueSpice\Permission\Lockdown\Module;
use Message;
use Title;
use User;

class SpecialPages extends Module {

	/** @var string */
	private const PUBLIC_WIKI = 'public';

	/** @var string[] */
	private const SPECIAL_PAGES_LOCKDOWN = [
		'AdminDashboard',
		'AutoblockList',
		'UserRights',
		'ListUsers',
		'BlockList',
		'PasswordReset',
		'SocialProfiles'
	];

	/**
	 * @inheritDoc
	 */
	public function applies( Title $title, User $user ) {
		if ( !$title->isSpecialPage() ) {
			return false;
		}
		$activePreset = $this->config->get( 'PermissionManagerActivePreset' );
		if ( $activePreset !== self::PUBLIC_WIKI ) {
			return false;
		}
		$userGroups = $this->getUserGroups( $user );
		if ( in_array( 'sysop', $userGroups ) ) {
			return false;
		}

		$specialPageFactory = $this->services->getSpecialPageFactory();
		foreach ( self::SPECIAL_PAGES_LOCKDOWN as $pageLockdown ) {
			$specialPage = $specialPageFactory->getPage( $pageLockdown )->getPageTitle();
			if ( $title->equals( $specialPage ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function mustLockdown( Title $title, User $user, $action ) {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function getLockdownReason( Title $title, User $user, $action ) {
		return Message::newFromKey( 'badaccess-groups', 'sysop' );
	}
}
