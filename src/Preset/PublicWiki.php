<?php

namespace BlueSpice\PermissionManager\Preset;

use MediaWiki\Message\Message;

class PublicWiki extends PermissionPreset {

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'public';
	}

	/**
	 * @inheritDoc
	 */
	public function getLabel(): Message {
		return Message::newFromKey( 'bs-permissionmanager-preset-public-label' );
	}

	/**
	 * @inheritDoc
	 */
	public function apply() {
		// Everyone can read, everyone can edit
		$this->groupRoles['*']['reader'] = true;
		$this->groupRoles['*']['editor'] = true;
		$this->groupRoles['bot']['bot'] = true;
	}

	/**
	 * @inheritDoc
	 */
	public function getIcon(): string {
		return 'public';
	}

	/**
	 * @inheritDoc
	 */
	public function getHelpMessage(): Message {
		return Message::newFromKey( 'bs-permissionmanager-preset-public-help' );
	}
}
