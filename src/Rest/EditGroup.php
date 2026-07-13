<?php

namespace BlueSpice\PermissionManager\Rest;

use BlueSpice\PermissionManager\GroupManager;
use BlueSpice\UserManager\Rest\EditGroup as RestEditGroup;
use MediaWiki\Rest\Response;

/**
 * @deprecated since BlueSpice 5.3 - use BlueSpice\UserManager\Rest\EditGroup
 */
class EditGroup extends RestEditGroup {

	/**
	 * @param GroupManager $groupManager
	 */
	public function __construct( GroupManager $groupManager ) {
		parent::__construct( $groupManager );
	}

	/**
	 * @return Response
	 */
	public function execute() {
		return parent::execute();
	}
}
