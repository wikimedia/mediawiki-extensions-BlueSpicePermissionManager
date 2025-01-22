<?php

namespace BlueSpice\PermissionManager;

use MediaWiki\Message\Message;
use MWException;

interface IPreset {
	/**
	 * @return string
	 */
	public function getId(): string;

	/**
	 * User-friendly display text
	 *
	 * @return Message
	 */
	public function getLabel(): Message;

	/**
	 * Explanation text for the preset
	 *
	 * @return Message
	 */
	public function getHelpMessage(): Message;

	/**
	 * Icon for the preset in the UI
	 *
	 * @return string
	 */
	public function getIcon(): string;

	/**
	 * Apply to the system
	 *
	 * @return array
	 * @throws MWException
	 */
	public function apply();
}
