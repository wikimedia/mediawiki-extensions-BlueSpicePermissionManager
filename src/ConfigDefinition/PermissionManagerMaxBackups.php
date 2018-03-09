<?php

namespace BlueSpice\PermissionManager\ConfigDefinition;

use BlueSpice\ConfigDefinition\IntSetting;

class PermissionManagerMaxBackups extends IntSetting {

	public function getPaths() {
		return [
			static::MAIN_PATH_FEATURE . '/' . static::FEATURE_ADMINISTRATION . '/BlueSpicePermissionManager',
			static::MAIN_PATH_EXTENSION . '/BlueSpicePermissionManager/' . static::FEATURE_ADMINISTRATION ,
			static::MAIN_PATH_PACKAGE . '/' . static::PACKAGE_FREE . '/BlueSpicePermissionManager',
		];
	}

	public function getLabelMessageKey() {
		return 'bs-permissionmanager-pref-max-backups';
	}

}
