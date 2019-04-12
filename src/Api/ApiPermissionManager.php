<?php

namespace BlueSpice\PermissionManager\Api;

class ApiPermissionManager extends \BSApiTasksBase {

	protected $aTasks = [
		'saveRoles' => [
			'examples' => [
				// TODO
			]
		]
	];

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
		return [
			'saveRoles' => [ 'wikiadmin' ]
		];
	}

	protected function task_saveRoles( $data ) {
		$ret = $this->makeStandardReturn();
		$ret->success = true;
		$arrRes = \BlueSpice\PermissionManager\Extension::saveRoles( $data );
		if ( is_array( $arrRes ) ) {
			if ( isset( $arrRes['success'] ) ) {
				$ret->success = $arrRes['success'];
			}
			if ( isset( $arrRes['message'] ) ) {
				$ret->message = $arrRes['message'];
			}
		} else {
			if ( $arrRes !== true ) {
				$ret->errors[] = $arrRes;
				$ret->success = false;
				$ret->message = wfMessage( "internalerror_info" )->params( $arrRes )->plain();
			}
		}

		return $ret;
	}

	public function __construct( $main, $action ) {
		parent::__construct( $main, $action );
	}

}
