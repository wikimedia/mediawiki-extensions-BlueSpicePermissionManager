<?php

namespace BlueSpice\PermissionManager\Preset;

use BlueSpice\PermissionManager\IPreset;
use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MWStake\MediaWiki\Component\DynamicConfig\DynamicConfigManager;

class CustomPreset implements IPreset {

	/** @var DynamicConfigManager */
	private $configManager;

	public static function factory() {
		return new static(
			MediaWikiServices::getInstance()->getService( 'MWStakeDynamicConfigManager' )
		);
	}

	/**
	 * @param DynamicConfigManager $configManager
	 */
	public function __construct( DynamicConfigManager $configManager ) {
		$this->configManager = $configManager;
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'custom';
	}

	/**
	 * @inheritDoc
	 */
	public function getLabel(): Message {
		return Message::newFromKey( 'bs-permissionmanager-preset-custom-label' );
	}

	/**
	 * Read and load settings file
	 */
	public function apply() {
		$config = $this->configManager->getConfigObject( 'bs-permissionmanager-roles' );
		$this->configManager->applyConfig( $config );
	}

	/**
	 * Parse and evaluate pm-settings file without applying it
	 * @return array|null on parse failure
	 */
	public function readOutSettings(): ?array {
		$raw = $this->configManager->retrieveRaw(
			$this->configManager->getConfigObject( 'bs-permissionmanager-roles' )
		);
		if ( $raw === null ) {
			return null;
		}
		return json_decode( $raw, true );
	}

	/**
	 * @inheritDoc
	 */
	public function getIcon(): string {
		return 'custom';
	}

	/**
	 * @inheritDoc
	 */
	public function getHelpMessage(): Message {
		return Message::newFromKey( 'bs-permissionmanager-preset-custom-help' );
	}
}
