<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ApiPermissionManager extends BSApiTasksBase {

	protected $aTasks = array(
		'savePermissions' => [
			'examples' => [
				[
					'groupPermission' => [
						'my_group' => [
							'read' => true,
							'edit' => false
						]
					],
					'permissionLockdown' => [
						NS_FILE => [
							'read' => [
								'user',
								'*',
								'my_other_group'
							]
						]
					]
				]
			],
			'params' => [
				'groupPermission' => [
					'desc' => '',
					'type' => 'array',
					'required' => true
				],
				'permissionLockdown' => [
					'desc' => '',
					'type' => 'array',
					'required' => true
				]
			]
		],
		'permissions' => [
			'examples' => [
				[
					'group' => 'sysop'
				],
				[
					'group' => 'sysop',
					'revision' => '20170322131039'
				]
			],
			'params' => [
				'group' => [
					'desc' => 'Valid group name',
					'type' => 'string',
					'required' => true
				],
				'revision' => [
					'desc' => 'Timestamp of a revision',
					'type' => 'string',
					'required' => false
				]
			]
		],
		'setTemplateData' => [
			'examples' => [
				[
					'id' => 0,
					'text' => 'My template',
					'description' => 'Some description',
					'ruleSet' => [
						'read',
						'edit',
						'patrol'
					]
				],
				[
					'id' => 4,
					'text' => 'My template',
					'description' => 'Some description',
					'ruleSet' => [
						'read',
						'edit'
					]
				]
			],
			'params' => [
				'id' => [
					'desc' => '',
					'type' => 'integer',
					'required' => true
				],
				'text' => [
					'desc' => '',
					'type' => 'string',
					'required' => true
				],
				'ruleSet' => [
					'desc' => '',
					'type' => 'array',
					'required' => true
				],
				'description' => [
					'desc' => '',
					'type' => 'string',
					'required' => true
				]
			]
		],
		'deleteTemplate' => [
			'examples' => [
				[
					'id' => 18,
				]
			],
			'params' => [
				'id' => [
					'desc' => '',
					'type' => 'integer',
					'required' => true
				]
			]
		]
	);

	public function getTaskDataDefinitions() {
		return array(
			"setTemplateData" => array(
				"id" => array(
					"type" => "int",
					"required" => true,
					"default" => ''
				),
				"text" => array(
					"type" => "string",
					"required" => true,
					"default" => ''
				),
				"leaf" => array(
					"type" => "boolean",
					"required" => true,
					"default" => ''
				),
				"ruleSet" => array(
					"type" => "array",
					"required" => true,
					"default" => ''
				),
				"description" => array(
					"type" => "string",
					"required" => true,
					"default" => ''
				)
			),
			"deleteTemplate" => array(
				"id" => array(
					"type" => "int",
					"required" => true,
					"default" => ''
				)
			),
			"savePermissions" => array(
				"groupPermission" => array(
					"type" => "array",
					"required" => true,
					"default" => ''
				),
				"permissionLockdown" => array(
					"type" => "array",
					"required" => true,
					"default" => ''
				),
				)
		);
	}

	protected function getRequiredTaskPermissions() {
		return array(
			'deleteTemplate' => array( 'wikiadmin' ),
			'permissions' => array( 'wikiadmin' ),
			'savePermissions' => array( 'wikiadmin' ),
			'setTemplateData' => array( 'wikiadmin' ),
		);
	}

	protected function task_savePermissions( $oData ) {
		$oRet = $this->makeStandardReturn();
		$oRet->success = true;
		$arrRes = PermissionManager::savePermissions( $oData );

		if ( $arrRes !== true && ( !isset( $arrRes['success'] ) || $arrRes['success'] !== true ) ) {
			$oRet->errors[] = $arrRes;
			$oRet->message = wfMessage("internalerror_info")->params( $arrRes )->plain();
			$oRet->success = false;
		}

		return $oRet;
	}

	protected function task_setTemplateData( $oTaskData ) {
		$oRet = $this->makeStandardReturn();
		$arrRes = PermissionManager::setTemplateData( $oTaskData );
		$oRet->payload = $arrRes;
		$oRet->success = $arrRes[ "success" ];

		return $oRet;
	}

	protected function task_deleteTemplate( $oData ) {
		$oRet = $this->makeStandardReturn();
		$arrRes = PermissionManager::deleteTemplate( $oData->id );
		$oRet->payload = $arrRes;
		$oRet->success = $arrRes[ "success" ];

		return $oRet;
	}

	protected function task_permissions( $oData ) {
		$oRet = $this->makeStandardReturn();
		//is revision requested by timestamp? default = current
		$arrData = array();
		if ( !isset( $oData->revision ) ) {
			//remove old permissions and override by including file
			$arrData = PermissionManager::getPermissionArray( $oData->group );
		} else {
			$arrData = PermissionManager::getPermissionArray( $oData->group, $oData->revision );
		}

		//return permissions for requested revision
		$arrResult = array(
			'result' => 'Success',
			'data' => $arrData
		);
		$oRet->success = true;
		//todo: add xml output handler, actualy this is only working for json
		$oRet->payload = $arrResult;

		return $oRet;
	}

	public function __construct( $main, $action ) {
		parent::__construct( $main, $action );
	}

}
