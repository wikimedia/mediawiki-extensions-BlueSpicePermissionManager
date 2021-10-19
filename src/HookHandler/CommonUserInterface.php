<?php

namespace BlueSpice\PermissionManager\HookHandler;

use BlueSpice\PermissionManager\GlobalActionsManager;
use MWStake\MediaWiki\Component\CommonUserInterface\Hook\MWStakeCommonUIRegisterSkinSlotComponents;

class CommonUserInterface implements MWStakeCommonUIRegisterSkinSlotComponents {

	/**
	 * @inheritDoc
	 */
	public function onMWStakeCommonUIRegisterSkinSlotComponents( $registry ): void {
		$registry->register(
			'GlobalActionsManager',
			[
				'special-bluespice-permissionmanager' => [
					'factory' => static function () {
						return new GlobalActionsManager();
					}
				]
			]
		);
	}
}
