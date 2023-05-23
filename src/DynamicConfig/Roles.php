<?php

namespace BlueSpice\PermissionManager\DynamicConfig;

use MWStake\MediaWiki\Component\DynamicConfig\GlobalsAwareDynamicConfig;
use MWStake\MediaWiki\Component\DynamicConfig\IDynamicConfig;

class Roles implements IDynamicConfig, GlobalsAwareDynamicConfig {
	/** @var array */
	private $mwGlobals;

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
			$this->mwGlobals[$global] = array_merge( $this->mwGlobals[$global] ?? [], $value );
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

	/**
	 * @param array &$globals
	 *
	 * @return mixed|void
	 */
	public function setMwGlobals( array &$globals ) {
		$this->mwGlobals = &$globals;
	}
}
