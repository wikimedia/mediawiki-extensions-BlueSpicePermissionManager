<?php

namespace BlueSpice\PermissionManager\ConfigDefinition;

use BlueSpice\ConfigDefinition\IntSetting;

class PermissionManagerMaxBackups extends IntSetting {
	public function getLabelMessageKey() {
		return 'bs-permissionmanager-pref-max-backups';
	}
}