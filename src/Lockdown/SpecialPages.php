<?php

namespace BlueSpice\PermissionManager\Lockdown;

use BlueSpice\Permission\Lockdown\Module;
use Message;
use Title;
use User;

class SpecialPages extends Module {

	/** @var string[] */
	private const SPECIAL_PAGES_LOCKDOWN = [
		'ActiveUsers',
		'AutoblockList',
		'UserRights',
		'ListUsers',
		'BlockList',
		'SocialProfiles'
	];

	/**
	 * @inheritDoc
	 */
	public function applies( Title $title, User $user ) {
		if ( defined( 'MW_QUIBBLE_CI' ) ) {
			return false;
		}
		if ( !$title->isSpecialPage() ) {
			return false;
		}
		$userGroups = $this->getUserGroups( $user );
		if ( in_array( 'sysop', $userGroups ) ) {
			return false;
		}

		$specialPageFactory = $this->services->getSpecialPageFactory();
		foreach ( self::SPECIAL_PAGES_LOCKDOWN as $pageLockdown ) {
			$specialPage = $specialPageFactory->getPage( $pageLockdown );
			if ( !$specialPage ) {
				continue;
			}
			if ( $title->equals( $specialPage->getPageTitle() ) ) {
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
