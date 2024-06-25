<?php

namespace BlueSpice\PermissionManager\ConfigDefinition;

use BlueSpice\ConfigDefinition\StringSetting;

/**
 * This config will not be included in ConfigManager, but is
 * set from Special:PermissionManager instead
 *
 */
class PermissionManagerActivePresetConfig extends StringSetting {

	/**
	 * @return string[]
	 */
	public function getPaths() {
		return [];
	}

	/**
	 * @return string
	 */
	public function getLabelMessageKey() {
		return '';
	}

	/**
	 * @return bool
	 */
	public function isHidden() {
		return true;
	}
}
