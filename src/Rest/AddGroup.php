<?php

namespace BlueSpice\PermissionManager\Rest;

use BlueSpice\PermissionManager\GroupManager;
use BlueSpice\UserManager\Rest\AddGroup as RestAddGroup;

/**
 * @deprecated since BlueSpice 5.3 - use BlueSpice\UserManager\Rest\AddGroup
 */
class AddGroup extends RestAddGroup {

	/**
	 * Undocumented function
	 *
	 * @param GroupManager $groupManager
	 */
	public function __construct( GroupManager $groupManager ) {
		return parent::__construct( $groupManager );
	}

	public function execute() {
		return parent::execute();
	}

}
