<?php

namespace BlueSpice\PermissionManager\Hook;

use Exception;
use MediaWiki\Permissions\Authority;

interface BSPermissionManagerGroupAddedHook {
	/**
	 * @param string $name
	 * @param Authority $actor
	 * @return void
	 * @throws Exception
	 */
	public function onBSPermissionManagerGroupAdded( string $name, Authority $actor );
}
