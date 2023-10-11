<?php

namespace BlueSpice\PermissionManager\HookHandler;

use BlueSpice\PermissionManager\GlobalActionsAdministration;
use MWStake\MediaWiki\Component\CommonUserInterface\Hook\MWStakeCommonUIRegisterSkinSlotComponents;

class CommonUserInterface implements MWStakeCommonUIRegisterSkinSlotComponents {

	/**
	 * @inheritDoc
	 */
	public function onMWStakeCommonUIRegisterSkinSlotComponents( $registry ): void {
		$registry->register(
			'GlobalActionsAdministration',
			[
				'special-bluespice-permissionmanager' => [
					'factory' => static function () {
						return new GlobalActionsAdministration();
					}
				]
			]
		);
	}
}
