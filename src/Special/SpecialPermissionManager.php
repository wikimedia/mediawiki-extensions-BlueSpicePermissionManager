<?php

namespace BlueSpice\PermissionManager\Special;

use BlueSpice\PermissionManager\PermissionManager;
use MediaWiki\Html\Html;
use MediaWiki\SpecialPage\SpecialPage;

class SpecialPermissionManager extends SpecialPage {
	/** @var PermissionManager */
	protected $permissionManager;

	/**
	 *
	 * @var array
	 */
	protected $groups = [];

	public function __construct( PermissionManager $permissionManager ) {
		parent::__construct( 'PermissionManager', 'wikiadmin' );

		$this->permissionManager = $permissionManager;
	}

	/**
	 * @inheritDoc
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$availablePresets = $this->permissionManager->getAvailablePresets();
		$activePreset = $this->permissionManager->getActivePreset();
		$presetData = [];
		foreach ( $availablePresets as $presetName ) {
			$preset = $this->permissionManager->getPreset( $presetName );
			if ( $preset === null ) {
				continue;
			}
			$presetData[$preset->getId()] = [
				'id' => $preset->getId(),
				'label' => $preset->getLabel()->text(),
				'help' => $preset->getHelpMessage()->parse(),
				'icon' => $preset->getIcon(),
				'active' => $activePreset->getId() === $preset->getId(),
			];
		}

		$this->getOutput()->addHTML(
			Html::element( 'div', [
				'id' => 'bs-permission-manager-preset-select',
				'data' => json_encode( $presetData )
			] )
		);
		$this->getOutput()->addHTML(
			Html::element( 'div', [
				'id' => 'bs-permission-manager-custom-preset',
				'class' => 'loading'
			] )
		);

		$this->getOutput()->addModules( [ 'ext.bluespice.permissionManager.presetSelector' ] );
	}
}
