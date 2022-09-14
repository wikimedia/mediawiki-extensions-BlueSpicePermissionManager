<?php

namespace BlueSpice\PermissionManager\Api;

use BlueSpice\Api\Response\Standard;
use BlueSpice\PermissionManager\PermissionManager;

class ApiPermissionManager extends \BSApiTasksBase {

	/**
	 *
	 * @var array
	 */
	protected $aTasks = [
		'saveRoles' => [
			'examples' => [
				// TODO
			]
		]
	];

	/**
	 *
	 * @return array
	 */
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

	/**
	 *
	 * @return array
	 */
	protected function getRequiredTaskPermissions() {
		return [
			'saveRoles' => [ 'wikiadmin' ]
		];
	}

	/**
	 *
	 * @param \stdClass $data
	 * @return Standard
	 */
	protected function task_saveRoles( $data ) {
		$ret = $this->makeStandardReturn();
		$ret->success = true;

		/** @var PermissionManager $permissionManager */
		$permissionManager = $this->services->getService( 'BlueSpicePermissionManager' );
		$arrRes = $permissionManager->saveRoles( $data );
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

}
