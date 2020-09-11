<?php

namespace BlueSpice\PermissionManager\Preset;

use Message;

class PrivateWiki extends PermissionPreset {

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'private';
	}

	/**
	 * @inheritDoc
	 */
	public function getLabel(): string {
		return Message::newFromKey( 'bs-permissionmanager-preset-private-label' )->text();
	}

	/**
	 * @inheritDoc
	 */
	public function apply() {
		// Everyone can read, only users can edit
		$this->groupRoles['*']['reader'] = false;
		$this->groupRoles['*']['editor'] = false;
		$this->groupRoles['user']['reader'] = true;
		$this->groupRoles['user']['editor'] = false;
		$this->groupRoles['editor']['editor'] = true;
		$this->groupRoles['sysop']['editor'] = true;
	}

	/**
	 * @inheritDoc
	 */
	public function getIcon(): string {
		return 'key';
	}

	/**
	 * @inheritDoc
	 */
	public function getHelpMessage(): string {
		return Message::newFromKey( 'bs-permissionmanager-preset-private-help' )->text();
	}
}
