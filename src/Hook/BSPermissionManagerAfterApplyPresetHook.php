<?php

namespace BlueSpice\PermissionManager\Hook;

use BlueSpice\Permission\RoleManager;
use BlueSpice\PermissionManager\IPreset;
use Exception;

interface BSPermissionManagerAfterApplyPresetHook {
	/**
	 * Called after a permission preset has been applied
	 * Safe place to override roles and permissions
	 *
	 * @param IPreset $preset
	 * @param RoleManager $roleManager
	 * @return void
	 * @throws Exception
	 */
	public function onBSPermissionManagerAfterApplyPreset( IPreset $preset, RoleManager $roleManager );
}
