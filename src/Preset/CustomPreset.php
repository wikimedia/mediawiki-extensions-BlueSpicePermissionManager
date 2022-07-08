<?php

namespace BlueSpice\PermissionManager\Preset;

use BlueSpice\PermissionManager\IPreset;
use Message;
use ParseError;

class CustomPreset implements IPreset {
	/** @var string */
	private $settingsFile;

	public static function factory() {
		return new static( BSCONFIGDIR . '/pm-settings.php' );
	}

	/**
	 * @inheritDoc
	 */
	public function __construct( $settingFilePath ) {
		$this->settingsFile = $settingFilePath;
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
		// :-/
		if ( file_exists( $this->settingsFile ) ) {
			include $this->settingsFile;
		}
	}

	/**
	 * Parse and evaluate pm-settings file without applying it
	 * @return array|null on parse failure
	 */
	public function evaluateSettingsFile(): ?array {
		if ( !file_exists( $this->settingsFile ) ) {
			return [];
		}
		$content = file_get_contents( $this->settingsFile );
		$content = preg_replace( '/\$GLOBALS/', '$roles', $content );
		$content = preg_replace( '/<\?php/', '', $content );

		try {
			$roles = [];
			eval( $content );
			return $roles;
		} catch ( ParseError $ex ) {
			return null;
		}
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
