<?php

class PermissionValidator {

	/**
	 * check if read permission on global level is set minimum for group sysop
	 * @param array $aLockdown
	 * @param array $aGroupPermissions
	 */
	public static function beforeSavePermissionsValidateGlobalRead( &$aLockdown, &$aGroupPermissions, &$aResult ) {
		$arrGroupPermissions = ( array ) $aGroupPermissions; //important for access, because object->* wouldnt work
		$boolReadGlobal = (isset( $arrGroupPermissions[ '*' ]->read )) ? $arrGroupPermissions[ '*' ]->read : false;
		$boolReadUser = (isset( $arrGroupPermissions[ 'user' ]->read )) ? $arrGroupPermissions[ 'user' ]->read : false;
		$boolReadSysop = (isset( $arrGroupPermissions[ 'sysop' ]->read )) ? $arrGroupPermissions[ 'sysop' ]->read : false;
		$boolGlobalRead = ($boolReadGlobal || $boolReadUser || $boolReadSysop);

		if(!$boolGlobalRead) {
			$aResult = array(
					'success' => false,
					'message' => wfMessage( 'bs-permissionmanager-error-lockout', 'read' )->plain()
			);
		}
		return true;
	}

	/**
	 * check if wikiadmin permission on global level is set minimum for group sysop
	 * @param array $aLockdown
	 * @param array $aGroupPermissions
	 */
	public static function beforeSavePermissionsValidateGlobalWikiadmin( &$aLockdown, &$aGroupPermissions, &$aResult ) {
		$arrGroupPermissions = ( array ) $aGroupPermissions; //important for access, because object->* wouldnt work
		$boolReadGlobal = ( isset( $arrGroupPermissions[ '*' ]->wikiadmin ) ) ? $arrGroupPermissions[ '*' ]->wikiadmin : false;
		$boolReadUser = ( isset( $arrGroupPermissions[ 'user' ]->wikiadmin ) ) ? $arrGroupPermissions[ 'user' ]->wikiadmin : false;
		$boolReadSysop = ( isset( $arrGroupPermissions[ 'sysop' ]->wikiadmin ) ) ? $arrGroupPermissions[ 'sysop' ]->wikiadmin : false;
		$boolGlobalRead = ( $boolReadGlobal || $boolReadUser || $boolReadSysop );

		if(!$boolGlobalRead) {
			$aResult = array(
					'success' => false,
					'msg' => wfMessage( 'bs-permissionmanager-error-lockout', 'wikiadmin' )->plain()
			);
		}
		return true;
	}

	/**
	 * check if edit permission on global level is set minimum for group sysop
	 * @param array $aLockdown
	 * @param array $aGroupPermissions
	 */
	public static function beforeSavePermissionsValidateGlobalEdit( &$aLockdown, &$aGroupPermissions, &$aResult ) {
		$arrGroupPermissions = ( array ) $aGroupPermissions; //important for access, because object->* wouldnt work
		$boolReadGlobal = (isset( $arrGroupPermissions[ '*' ]->edit )) ? $arrGroupPermissions[ '*' ]->edit : false;
		$boolReadUser = (isset( $arrGroupPermissions[ 'user' ]->edit )) ? $arrGroupPermissions[ 'user' ]->edit : false;
		$boolReadSysop = (isset( $arrGroupPermissions[ 'sysop' ]->edit )) ? $arrGroupPermissions[ 'sysop' ]->edit : false;
		$boolGlobalRead = ($boolReadGlobal || $boolReadUser || $boolReadSysop);

		if(!$boolGlobalRead) {
			$aResult = array(
					'success' => false,
					'msg' => wfMessage( 'bs-permissionmanager-error-lockout', 'edit' )->plain()
			);
		}
		return true;
	}

}
