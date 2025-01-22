<?php

namespace BlueSpice\PermissionManager\Preset;

use MediaWiki\Message\Message;

class ProtectedWiki extends PermissionPreset {

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'protected';
	}

	/**
	 * @inheritDoc
	 */
	public function getLabel(): Message {
		return Message::newFromKey( 'bs-permissionmanager-preset-protected-label' );
	}

	/**
	 * @inheritDoc
	 */
	public function apply() {
		// Everyone can read, only users can edit
		$this->groupRoles['*']['reader'] = true;
		$this->groupRoles['*']['editor'] = false;
		$this->groupRoles['user']['editor'] = true;
		$this->groupRoles['bot']['bot'] = true;
	}

	/**
	 * @inheritDoc
	 */
	public function getIcon(): string {
		return 'protected';
	}

	/**
	 * @inheritDoc
	 */
	public function getHelpMessage(): Message {
		return Message::newFromKey( 'bs-permissionmanager-preset-protected-help' );
	}
}
