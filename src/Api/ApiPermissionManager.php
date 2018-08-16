<?php

namespace BlueSpice\PermissionManager\Api;

class ApiPermissionManager extends \BSApiTasksBase {

	protected $aTasks = array(
		'saveRoles' => [
			'examples' => [
				//TODO
			]
		]
	);

	public function getTaskDataDefinitions() {
		return [
			"saveRoles" => [
				"groupRoles" => [
					"type" => "array",
					"required" => true,
					"default" => ''
				],
				"roleLockdown" => [
					"type" => "array",
					"required" => true,
					"default" => ''
				],
			]
		];
	}

	protected function getRequiredTaskPermissions() {
		return array(
			'saveRoles' => array( 'wikiadmin' )
		);
	}

	protected function task_saveRoles( $data ) {
		$ret = $this->makeStandardReturn();
		$ret->success = true;
		$arrRes = \BlueSpice\PermissionManager\Extension::saveRoles( $data );

		if ( $arrRes !== true && ( !isset( $arrRes['success'] ) || $arrRes['success'] !== true ) ) {
			$ret->errors[] = $arrRes;
			$ret->message = wfMessage("internalerror_info")->params( $arrRes )->plain();
			$ret->success = false;
		}

		return $ret;
	}

	public function __construct( $main, $action ) {
		parent::__construct( $main, $action );
	}

}
