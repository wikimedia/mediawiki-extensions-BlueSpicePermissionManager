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
				// Normalization for legacy data where array is keyed
				foreach ( $value as &$roles ) {
					foreach ( $roles as &$groups ) {
						$groups = array_values( array_unique( $groups ) );
					}
				}
				$value = $this->mergeWithGlobal( $GLOBALS[$global], $value );
			} else {
				$value = array_merge( $GLOBALS[$global] ?? [], $value );
			}

			$GLOBALS[$global] = $value;
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
				if ( is_array( $roles ) && !empty( $roles ) ) {
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
		foreach ( $additionalData['groupRoles'] as $group => &$data ) {
			if ( $data === null ) {
				$data = [];
			}
		}
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

	/**
	 * @param mixed $global
	 * @param array $value
	 *
	 * @return array
	 */
	private function mergeWithGlobal( $global, array $value ) {
		if ( !is_array( $global ) ) {
			return $value;
		}
		// Add settings for namespaces and roles that are NOT set in dynamic config
		// otherwise use dynamic config values
		foreach ( $global as $ns => $data ) {
			if ( isset( $value[$ns] ) ) {
				$value[$ns] = array_merge( $data, $value[$ns] );
			} else {
				$value[$ns] = $data;
			}
		}
		return $value;
	}
}
