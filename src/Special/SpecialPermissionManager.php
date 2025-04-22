<?php

namespace BlueSpice\PermissionManager\Special;

use BlueSpice\PermissionManager\PermissionManager;
use MediaWiki\Html\Html;
use MediaWiki\Html\TemplateParser;
use OOJSPlus\Special\OOJSSpecialPage;

class SpecialPermissionManager extends OOJSSpecialPage {
	/** @var PermissionManager */
	protected $permissionManager;

	/**
	 *
	 * @var array
	 */
	protected $groups = [];

	/**
	 * @param PermissionManager $permissionManager
	 */
	public function __construct( PermissionManager $permissionManager ) {
		parent::__construct( 'PermissionManager', 'wikiadmin' );

		$this->permissionManager = $permissionManager;
		$this->templateParser = new TemplateParser(
			dirname( __DIR__, 2 ) . '/resources/templates'
		);
	}

	/**
	 *
	 * @return void
	 */
	protected function buildSkeleton() {
		$this->getOutput()->enableOOUI();
		$this->getOutput()->addModuleStyles( [ 'ext.bluespice.permissionManager.skeleton' ] );
		$skeleton = $this->templateParser->processTemplate(
			'skeleton-permission',
			[]
		);
		$skeletonCnt = Html::openElement( 'div', [
			'id' => 'bs-permissionManager-skeleton-cnt'
		] );
		$skeletonCnt .= $skeleton;
		$skeletonCnt .= Html::closeElement( 'div' );
		$this->getOutput()->addHTML( $skeletonCnt );
	}

	/**
	 * @inheritDoc
	 */
	public function doExecute( $subPage ) {
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
