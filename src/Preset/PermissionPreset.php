<?php

namespace BlueSpice\PermissionManager\Preset;

use BlueSpice\PermissionManager\IPreset;

abstract class PermissionPreset implements IPreset {
	/** @var array */
	protected $groupPermissions = [];
	/** @var array */
	protected $groupRoles = [];

	/**
	 * @return static
	 */
	public static function factory() {
		return new static(
			$GLOBALS[ 'wgGroupPermissions' ],
			$GLOBALS[ 'bsgGroupRoles' ]
		);
	}

	/**
	 * @param array &$groupPermissions
	 * @param array &$groupRoles
	 */
	public function __construct( &$groupPermissions, &$groupRoles ) {
		$this->groupPermissions =& $groupPermissions;
		$this->groupRoles =& $groupRoles;
	}
}
