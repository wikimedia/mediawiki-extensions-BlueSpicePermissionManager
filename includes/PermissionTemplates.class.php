<?php

/**
 * Description of PermissionTemplates
 *
 * @author Sebastian Ulbricht <sebastian.ulbricht@gmx.de>
 */
class PermissionTemplates {
	protected static $_aTemplates = array();

	/**
	 *
	 * @return array
	 */
	public static function getAll() {
		if(!count(self::$_aTemplates)) {
			$oDb        = wfGetDB(DB_REPLICA);
			$sTableName = $oDb->tableName('bs_permission_templates');
			$oRes = $oDb->query("SELECT tpl_id, tpl_name, tpl_data, tpl_description
							 FROM {$sTableName}");

			while($row = $oRes->fetchObject()) {
				self::$_aTemplates[] = new PermissionTemplates($row->tpl_id, $row->tpl_name, unserialize($row->tpl_data), $row->tpl_description);
			}
		}

		return self::$_aTemplates;
	}

	public static function getPermissionsFromName($sTplName) {
		$oDb = wfGetDB(DB_REPLICA);
		$sTableName = $oDb->tableName('bs_permission_templates');
		$oRes = $oDb->query("SELECT tpl_data
							 FROM {$sTableName}
							 WHERE tpl_name = '{$sTplName}'");
		$row = $oRes->fetchObject();
		return unserialize($row->tpl_data);
	}

	public static function addTemplate($sName, $aPermissions, $sDescription ) {
		$oDb = wfGetDB(DB_MASTER);
		$sTableName = $oDb->tableName('bs_permission_templates');
		return $oDb->insert($sTableName, array(
			'tpl_name' => $sName,
			'tpl_data' => serialize($aPermissions),
			'tpl_description' => $sDescription
		  ));
	}

	public static function editTemplate($iId, $sName, $aPermissions, $sDescription) {
		$oDb = wfGetDB(DB_MASTER);
		$sTableName = $oDb->tableName('bs_permission_templates');
		return $oDb->query("UPDATE {$sTableName}
							SET tpl_name = '{$sName}', tpl_data = '".serialize($aPermissions)."', tpl_description = '{$sDescription}'
							WHERE tpl_id = {$iId}");
	}

	public static function removeTemplate($iId) {
		$oDb = wfGetDB(DB_MASTER);
		$sTableName = $oDb->tableName('bs_permission_templates');
		return $oDb->query("DELETE FROM {$sTableName}
							WHERE tpl_id = {$iId}");
	}

	protected $iId			= NULL;
	protected $sName        = NULL;
	protected $aPermissions = array();
	protected $sDescription = NULL;

	protected function __construct($iId, $sName, $aPermissions, $sDescription) {
		wfProfileIn( 'BS::'.__METHOD__ );
		$this->iId = $iId;
		$this->sName = $sName;
		$this->aPermissions = $aPermissions;
		$this->sDescription = $sDescription;
		wfProfileOut( 'BS::'.__METHOD__ );
	}

	public function getId() {
		return $this->iId;
	}

	public function getName() {
		return $this->sName;
	}

	public function getPermissions() {
		return $this->aPermissions;
	}

	public function getDescription() {
		return $this->sDescription;
	}

	public function checkPermissions(&$aPermissions) {
		$bCheck = true;
		foreach($this->aPermissions as $sPermission) {
			if(!isset($aPermissions[$sPermission])) {
				// TODO SU (04.07.11 12:08): könnte man hier nicht gleich return false zurückgeben?
				$bCheck = false;
			}
		}
		if($bCheck) {
			foreach($this->aPermissions as $sPermission) {
				if(isset($aPermissions[$sPermission])) {
					$aPermissions[$sPermission] = true;
				}
			}
		}

		return $bCheck;
	}
}
