<?php

namespace BlueSpice\PermissionManager;

use MWException;

interface IPreset {
	/**
	 * @return string
	 */
	public function getId(): string;

	/**
	 * User-friendly display text
	 *
	 * @return string
	 */
	public function getLabel(): string;

	/**
	 * Explanation text for the preset
	 *
	 * @return string
	 */
	public function getHelpMessage(): string;

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
