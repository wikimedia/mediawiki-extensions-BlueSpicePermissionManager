<?php

namespace BlueSpice\PermissionManager\Lockdown;

use BlueSpice\Permission\Lockdown\Module;
use BlueSpice\Permission\RoleManager;
use MediaWiki\Message\Message;
use MediaWiki\Title\Title;
use MediaWiki\User\User;

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
		if ( !$this->isLockdownPage( $title ) ) {
			return false;
		}
		if ( $this->hasAdminRole( $user ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @param Title $title
	 * @return bool
	 */
	private function isLockdownPage( Title $title ): bool {
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
	 * @param User $user
	 * @return bool
	 */
	private function hasAdminRole( User $user ): bool {
		$userGroups = $this->getUserGroups( $user );
		/** @var RoleManager $roleManager */
		$roleManager = $this->services->getService( 'BSRoleManager' );
		foreach ( $userGroups as $group ) {
			$groupRoles = $roleManager->getGroupRoles( $group );
			foreach ( $groupRoles as $group => $roles ) {
				foreach ( $roles as $role => $active ) {
					if ( $role === 'admin' && $active === true ) {
						return true;
					}
				}
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
		return Message::newFromKey( 'badaccess-group0' );
	}
}
