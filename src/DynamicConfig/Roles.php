<?php

namespace BlueSpice\PermissionManager\DynamicConfig;

use MWStake\MediaWiki\Component\DynamicConfig\IDynamicConfig;

class Roles implements IDynamicConfig {

	/**
	 * @return string
	 */
	public function getKey(): string {
		return 'bs-permissionmanager-roles';
	}

	/**
	 * @param string $serialized
	 *
	 * @return bool
	 */
	public function apply( string $serialized ): bool {
		$unserialized = json_decode( $serialized, true );
		foreach ( $unserialized as $global => $value ) {
			$GLOBALS[$global] = array_merge( $GLOBALS[$global] ?? [], $value );
		}
		return true;
	}

	/**
	 * @param array|null $additionalData
	 *
	 * @return string
	 */
	public function serialize( ?array $additionalData = [] ): string {
		return json_encode( [
			'bsgGroupRoles' => $additionalData[ 'groupRoles' ] ?? [],
			'bsgNamespaceRolesLockdown' => $additionalData[ 'roleLockdown' ] ?? [],
		] );
	}

	/**
	 * @return bool
	 */
	public function shouldAutoApply(): bool {
		return false;
	}
}
