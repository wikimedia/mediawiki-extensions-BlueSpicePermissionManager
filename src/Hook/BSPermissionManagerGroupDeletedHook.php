<?php

namespace BlueSpice\PermissionManager\Hook;

use Exception;
use MediaWiki\Permissions\Authority;

interface BSPermissionManagerGroupDeletedHook {
	/**
	 * @param string $name
	 * @param Authority $actor
	 * @return void
	 * @throws Exception
	 */
	public function onBSPermissionManagerGroupDeleted( string $name, Authority $actor );
}
