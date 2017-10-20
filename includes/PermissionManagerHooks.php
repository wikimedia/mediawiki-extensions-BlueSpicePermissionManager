<?php

/*
 * Hook collection for PermissionManager
 */

class PermissionManagerHooks {

	/**
	 *
	 * @return array for default permission right template
	 */
	public static function defaultTemplateReadGeneralTitle() {
		//Not namespace specific
		return array(
			'bs-permissionmanager-default-template-read-general-title' => array(
				//BlueSpice
				//TODO: Move to other extensions
				'files',
				'viewfiles',
				'searchfiles'
			)
		);
	}

	/**
	 *
	 * @return array for default permission right template
	 */
	public static function defaultTemplateReadTitle() {
		return array(
			'bs-permissionmanager-default-template-read-title' => array(
				//MediaWiki standard
				'read',
				//BlueSpice
				//TODO: Move to other extensions
				'readshoutbox',
				'universalexport-export',
				'universalexport-export-with-attachments'
			)
		);
	}

	/**
	 *
	 * @return array for default permission right template
	 */
	public static function defaultTemplateEditGeneralTitle() {
		return array(
			//Not namespace specific
			'bs-permissionmanager-default-template-edit-general-title' => array(
				//MediaWiki standard
				'movefile',
				'move-rootuserpages',
				'upload',
				'reupload',
				'reupload-own',
				'reupload-shared',
				'upload_by_url',
				'writeapi',
				//BlueSpice
				//TODO: Move to other extensions
				'writeshoutbox'
			)
		);
	}

	/**
	 *
	 * @return array for default permission right template
	 */
	public static function defaultTemplateEditTitle(){
		return array(
			'bs-permissionmanager-default-template-edit-title' => array(
				//MediaWiki standard
				'edit',
				'create',
				'createtalk',
				'move',
				'move-subbpages',
				'delete',
				//BlueSpice
				//TODO: Move to other extensions
				'writeshoutbox'
			)
		);
	}

	/**
	 *
	 * @return array for default permission right template
	 */
	public static function defaultTemplateAdminTitle(){
		return array(
			'bs-permissionmanager-default-template-admin-title' => array(
				//MediaWiki standard
				'bigdelete',
				'browsearchive',
				'createaccount',
				'deletedtext',
				'deletedhistory',
				'protect',
				'editprotected',
				'block',
				'rollback',
				'import',
				'userrights',
				//BlueSpice
				//TODO: Move to other extensions
				'wikiadmin',
				'editadmin', // still in use?
				'useradmin' // still in use?
			)
		);
	}

	/**
	 *
	 * @return array for default permission right template
	 */
	public static function defaultTemplateQualityTitle(){
		return array(
			'bs-permissionmanager-default-template-quality-title' => array(
				//MediaWiki FlaggedRevs
				//TODO: Move to other extensions
				'autoreview',
				'review',
				'unreviewdpages',
				'validate',
				//BlueSpice
				//TODO: Move to other extensions
				'responsibleeditors-changeresponsibility',
				'responsibleeditors-takeresponsibility',
				'responsibleeditors-viewspecialpage',
				'workflowview',
				'workflowedit', // still in use?
			)
		);
	}

	/**
	 * Set default right permission templates in global used vars
	 * @return true
	 */
	public static function onCallback() {
		global $bsgPermissionManagerDefaultTemplates;

		if ( !isset( $bsgPermissionManagerDefaultTemplates ) ) {
			$bsgPermissionManagerDefaultTemplates = array();
		}

		global $bsgConfigFiles;
		$bsgConfigFiles[ 'PermissionManager' ] = BSCONFIGDIR . DS . 'pm-settings.php';

		//set config for Permissionmanager::preventPermissionLockout
		global $bsgPermissionConfig;
		$bsgPermissionConfig[ 'read' ][ 'preventLockout' ] = true;
		$bsgPermissionConfig[ 'wikiadmin' ][ 'preventLockout' ] = true;
		$bsgPermissionConfig[ 'edit' ][ 'preventLockout' ] = true;

		$bsgPermissionManagerDefaultTemplates = array_merge(
		  $bsgPermissionManagerDefaultTemplates,
		  self::defaultTemplateReadGeneralTitle(),
		  self::defaultTemplateReadTitle(),
		  self::defaultTemplateEditGeneralTitle(),
		  self::defaultTemplateEditTitle(),
		  self::defaultTemplateAdminTitle(),
		  self::defaultTemplateQualityTitle()
		);

		return true;
	}
}
