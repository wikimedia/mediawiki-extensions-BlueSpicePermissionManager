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
			if ( $global === 'bsgNamespaceRolesLockdown' ) {
				// In some setups it was discovered that this global is set to a value
				// of group names, but empty roles. Such setting will, instead of disregarding
				// locking, actually lock down for everybody. So we need to remove those
				// Why this value appeared in the DB is still unclear
				$value = $this->removeEmptyValues( $value );
			}
			$GLOBALS[$global] = array_merge( $GLOBALS[$global] ?? [], $value );
		}
		return true;
	}

	/**
	 * Remove values like `[NS_MAIN => [ 'editor' => [] ] ]`,
	 * but keep [NS_MAIN => [ 'editor' => [ 'reader' ] ] ]`
	 * @param array $values
	 *
	 * @return array
	 */
	protected function removeEmptyValues( array $values ): array {
		$final = [];
		foreach ( $values as $ns => $data ) {
			foreach ( $data as $group => $roles ) {
				if ( !empty( $roles ) ) {
					if ( !isset( $final[$ns] ) ) {
						$final[$ns] = [];
					}
					$final[$ns][$group] = $roles;
				}
			}
		}

		return $final;
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
