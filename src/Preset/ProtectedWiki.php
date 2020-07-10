<?php

namespace BlueSpice\PermissionManager\Preset;

use Message;

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
	public function getLabel(): string {
		return Message::newFromKey( 'bs-permissionmanager-preset-protected-label' )->text();
	}

	/**
	 * @inheritDoc
	 */
	public function apply() {
		// Everyone can read, only users can edit
		$this->groupRoles['*']['reader'] = true;
		$this->groupRoles['*']['editor'] = false;
		$this->groupRoles['user']['editor'] = true;
	}

	/**
	 * @inheritDoc
	 */
	public function getIcon(): string {
		return 'editLock';
	}

	/**
	 * @inheritDoc
	 */
	public function getHelpMessage(): string {
		return Message::newFromKey( 'bs-permissionmanager-preset-protected-help' )->text();
	}
}
