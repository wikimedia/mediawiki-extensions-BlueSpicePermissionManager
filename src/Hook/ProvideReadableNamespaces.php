<?php

namespace BlueSpice\PermissionManager\Hook;

use MediaWiki\Config\ConfigFactory;
use MWStake\MediaWiki\Component\Utils\Hook\MWStakeUtilsGetReadableNamespacesHook;

class ProvideReadableNamespaces implements MWStakeUtilsGetReadableNamespacesHook {

	/**
	 * @param ConfigFactory $configFactory
	 */
	public function __construct(
		public readonly ConfigFactory $configFactory
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function onMWStakeUtilsGetReadableNamespaces( array $allNamespaces, array &$namespacePermissions ): bool {
		$config = $this->configFactory->makeConfig( 'bsg' );
		$groupRoles = $config->get( 'GroupRoles' );
		$namespaceLockdown = $config->get( 'NamespaceRolesLockdown' );

		$globalReaders = [];
		foreach ( $groupRoles as $group => $roles ) {
			if ( isset( $roles['reader'] ) && $roles['reader'] ) {
				$globalReaders[] = $group;
			}
		}
		foreach ( $allNamespaces as $ns ) {
			if ( isset( $namespaceLockdown[$ns] ) ) {
				$namespacePermissions[$ns] = $namespaceLockdown[$ns]['reader'] ?? [];
				continue;
			}
			$namespacePermissions[$ns] = $globalReaders;
		}
		return false;
	}
}
