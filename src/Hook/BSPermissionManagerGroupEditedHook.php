<?php

namespace BlueSpice\PermissionManager\Hook;

use Exception;
use MediaWiki\Permissions\Authority;

interface BSPermissionManagerGroupEditedHook {
	/**
	 * @param string $oldName
	 * @param string $newName
	 * @param Authority $actor
	 * @return void
	 * @throws Exception
	 */
	public function onBSPermissionManagerGroupEdited( string $oldName, string $newName, Authority $actor );
}
