<?php

namespace BlueSpice\PermissionManager\Hook;

use BlueSpice\PermissionManager\DynamicConfig\Groups;
use BlueSpice\PermissionManager\DynamicConfig\Roles;
use MWStake\MediaWiki\Component\DynamicConfig\Hook\MWStakeDynamicConfigRegisterConfigsHook;

class RegisterDynamicConfig implements MWStakeDynamicConfigRegisterConfigsHook {

	/**
	 * @inheritDoc
	 */
	public function onMWStakeDynamicConfigRegisterConfigs( array &$configs ): void {
		$configs[] = new Roles();
		$configs[] = new Groups();
	}
}
