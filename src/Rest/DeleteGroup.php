<?php

namespace BlueSpice\PermissionManager\Rest;

use BlueSpice\PermissionManager\GroupManager;
use BlueSpice\UserManager\Rest\DeleteGroup as RestDeleteGroup;
use MediaWiki\Rest\Response;

/**
 * @deprecated since BlueSpice 5.3 - use BlueSpice\UserManager\Rest\DeleteGroup
 */
class DeleteGroup extends RestDeleteGroup {

	/**
	 * Undocumented function
	 *
	 * @param GroupManager $groupManager
	 */
	public function __construct( GroupManager $groupManager ) {
		return parent::__construct( $groupManager );
	}

	/**
	 * @return Response
	 */
	public function execute() {
		return parent::execute();
	}
}
