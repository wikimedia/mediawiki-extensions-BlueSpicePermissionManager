<?php

namespace BlueSpice\PermissionManager\DynamicConfig;

use MWStake\MediaWiki\Component\DynamicConfig\GlobalsDynamicConfig;

class Groups extends GlobalsDynamicConfig {

	/**
	 * @return string
	 */
	public function getKey(): string {
		return 'bs-groupmanager-groups';
	}

	/**
	 * @return bool
	 */
	public function shouldAutoApply(): bool {
		return true;
	}

	/**
	 * @param string $serialized
	 *
	 * @return bool
	 */
	public function apply( string $serialized ): bool {
		parent::apply( $serialized );

		$groupPermissions = $this->getMwGlobal( 'wgGroupPermissions' );
		$additionalGroups = $this->getMwGlobal( 'wgAdditionalGroups' );

		foreach ( array_keys( $additionalGroups ) as $group ) {
			if ( isset( $groupPermissions[$group] ) ) {
				continue;
			}
			$groupPermissions[$group] = [];
		}
		$this->setMwGlobal( 'wgGroupPermissions', $groupPermissions );
		return true;
	}

	/**
	 * @param array|null $additionalData
	 *
	 * @return string
	 */
	public function serialize( ?array $additionalData = [] ): string {
		$toSerialize = [];
		foreach ( $additionalData as $group => $value ) {
			if ( $value === false ) {
				continue;
			}
			$toSerialize[$group] = [];
		}

		return serialize( [ 'wgAdditionalGroups' => $toSerialize ] );
	}

	/**
	 * @return string[]
	 */
	protected function getSupportedGlobals(): array {
		return [ 'wgAdditionalGroups' ];
	}
}
