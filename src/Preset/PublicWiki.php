<?php

namespace BlueSpice\PermissionManager\Preset;

use Message;

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
	public function getLabel(): string {
		return Message::newFromKey( 'bs-permissionmanager-preset-public-label' )->text();
	}

	/**
	 * @inheritDoc
	 */
	public function apply() {
		// Everyone can read, only users can edit
		$this->groupRoles['*']['reader'] = true;
		$this->groupRoles['*']['editor'] = true;
		// $this->groupRoles['user']['editor'] = true;
	}

	/**
	 * @inheritDoc
	 */
	public function getIcon(): string {
		// TODO: To use globe once oojs-ui-wikimediaui-icons-location file gets included
		return 'eye';
	}

	/**
	 * @inheritDoc
	 */
	public function getHelpMessage(): string {
		return Message::newFromKey( 'bs-permissionmanager-preset-public-help' )->text();
	}
}
