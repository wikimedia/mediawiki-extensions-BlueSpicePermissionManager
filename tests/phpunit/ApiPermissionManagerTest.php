<?php

/*
 * Test PermissionManager API Endpoints
 */

/**
 * @group BlueSpicePermissionManager
 * @group BlueSpice
 * @group API
 * @group Database
 * @group medium
 */

class ApiPermissionManagerTest extends BSApiTasksTestBase {

	protected $sGroup = "sysop";

	protected function getModuleName() {
		return 'bs-permissionmanager-tasks';
	}

	public function testSavePermissions() {
		$testData = $this->executeTask( 'permissions',[
		  "group" => $this->sGroup
		] );

		$testData->payload["data"]["bsPermissionManagerGroupPermissions"]["sysop"]["read"] = true;
		$testData->payload["data"]["bsPermissionManagerGroupPermissions"]["sysop"]["edit"] = true;
		$testData->payload["data"]["bsPermissionManagerGroupPermissions"]["sysop"]["siteadmin"] = true;

		$aPayload = [
			"groupPermission"=>$testData->payload["data"]["bsPermissionManagerGroupPermissions"],
			"permissionLockdown"=>$testData->payload["data"]["bsPermissionManagerPermissionLockdown"]
		];

		$data = $this->executeTask( 'savePermissions', $aPayload );

		$this->assertEquals( true, $data->success );
	}

	/**
	 * 1. create template
	 * 2. update template
	 * taskData:{"id":0,"text":"test1","leaf":true,"ruleSet":["aboutbluespice-viewspecialpage","apihighlimits","applychangetags"],"description":""}
	 * @return api return data
	 */
	public function testSetTemplateData() {
		$arrRuleSet = [ "aboutbluespice-viewspecialpage", "apihighlimits", "applychangetags" ];
		$sTemplateName = "TestTemplate1";
		$sTemplateDescription = "Test Description";

		//create new template
		$data = $this->executeTask( 'setTemplateData', [
			"id" => 0,
			"text" => $sTemplateName,
			"leaf" => true,
			"ruleSet" => $arrRuleSet,
			"description" => $sTemplateDescription
		] );

		$this->assertEquals( true, $data->success );

		$iId = $data->payload["id"];

		//update template
		$arrRuleSetUpdate = [ "aboutbluespice-viewspecialpage", "apihighlimits", "applychangetags", "createpage" ];
		$dataUpdate = $this->executeTask( 'setTemplateData', [
			"id" => $iId,
			"text" => $sTemplateName,
			"leaf" => true,
			"ruleSet" => $arrRuleSetUpdate,
			"description" => $sTemplateDescription
		] );

		$this->assertEquals( true, $dataUpdate->success );
	}

	public function testDeleteTemplate() {
		$arrRuleSet = [ "aboutbluespice-viewspecialpage", "apihighlimits", "applychangetags" ];
		$sTemplateName = "TestTemplate1";
		$sTemplateDescription = "Test Description";

		//create new template
		$dataCreate = $this->executeTask( 'setTemplateData',[
			"id" => 0,
			"text" => $sTemplateName,
			"leaf" => true,
			"ruleSet" => $arrRuleSet,
			"description" => $sTemplateDescription
		] );

		$iId = $dataCreate->payload["id"];

		$data = $this->executeTask( 'deleteTemplate', [
			"id" => $iId
		] );

		$this->assertEquals( true, $data->success );
	}

	public function testPermissions() {
		$data = $this->executeTask( 'permissions',[
		  "group" => $this->sGroup
		] );

		$this->assertEquals( true, $data->success );
		$this->assertNotNull("payload", $data->payload);
		$this->assertArrayHasKey("data", $data->payload);

		$this->assertArrayHasKey("bsPermissionManagerGroupsTree", $data->payload["data"]);
		$this->assertArrayHasKey("text", $data->payload["data"]["bsPermissionManagerGroupsTree"]);
		$this->assertArrayHasKey("builtin", $data->payload["data"]["bsPermissionManagerGroupsTree"]);
		$this->assertArrayHasKey("implicit", $data->payload["data"]["bsPermissionManagerGroupsTree"]);
		$this->assertArrayHasKey("expanded", $data->payload["data"]["bsPermissionManagerGroupsTree"]);
		$this->assertArrayHasKey("children", $data->payload["data"]["bsPermissionManagerGroupsTree"]);

		$this->assertArrayHasKey("bsPermissionManagerNamespaces", $data->payload["data"]);
		$this->assertArrayHasKey("id", reset($data->payload["data"]["bsPermissionManagerNamespaces"]));
		$this->assertArrayHasKey("name", reset($data->payload["data"]["bsPermissionManagerNamespaces"]));
		$this->assertArrayHasKey("hideable", reset($data->payload["data"]["bsPermissionManagerNamespaces"]));

		$this->assertArrayHasKey("bsPermissionManagerRights", $data->payload["data"]);
		$this->assertArrayHasKey("hint", reset($data->payload["data"]["bsPermissionManagerRights"]));
		$this->assertArrayHasKey("right", reset($data->payload["data"]["bsPermissionManagerRights"]));
		$this->assertArrayHasKey("type", reset($data->payload["data"]["bsPermissionManagerRights"]));
		$this->assertArrayHasKey("typeHeader", reset($data->payload["data"]["bsPermissionManagerRights"]));

		$this->assertArrayHasKey("bsPermissionManagerGroupPermissions", $data->payload["data"]);
		$this->assertArrayHasKey($this->sGroup, $data->payload["data"]["bsPermissionManagerGroupPermissions"]);

		$this->assertArrayHasKey("bsPermissionManagerPermissionLockdown", $data->payload["data"]);
	}

}