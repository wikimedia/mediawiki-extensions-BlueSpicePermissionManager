<?php

namespace BlueSpice\PermissionManager\Preset;

use MediaWiki\Message\Message;

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
	public function getLabel(): Message {
		return Message::newFromKey( 'bs-permissionmanager-preset-private-label' );
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
		$this->groupRoles['bot']['bot'] = true;
	}

	/**
	 * @inheritDoc
	 */
	public function getIcon(): string {
		return 'private';
	}

	/**
	 * @inheritDoc
	 */
	public function getHelpMessage(): Message {
		return Message::newFromKey( 'bs-permissionmanager-preset-private-help' );
	}
}
