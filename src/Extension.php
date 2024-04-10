<?php

/**
 * PermissionManager Extension for BlueSpice
 *
 * Administration interface for managing permissions.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * This file is part of BlueSpice MediaWiki
 * For further information visit https://bluespice.com
 *
 * @author     Dejan Savuljesku <savuljesku@hallowelt.com>
 * @package    BlueSpice_Extensions
 * @subpackage PermissionManager
 * @copyright  Copyright (C) 2018 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GPL-3.0-only
 * @filesource
 */

namespace BlueSpice\PermissionManager;

use MediaWiki\MediaWikiServices;

class Extension extends \BlueSpice\Extension {

	public static function onCallback() {
		// Do not apply permission changes within WMF CI, to avoid issues with core tests
		if ( defined( 'MW_QUIBBLE_CI' ) ) {
			return;
		}

		$GLOBALS['wgHooks']['SetupAfterCache'] = $GLOBALS['wgHooks']['SetupAfterCache'] ?? [];
		array_unshift( $GLOBALS['wgHooks']['SetupAfterCache'], static function () {
			// Earliest that we have DB service available
			/** @var PermissionManager $permissionManager */
			$permissionManager = MediaWikiServices::getInstance()->getService(
				'BlueSpicePermissionManager'
			);

			// Apply preset
			$permissionManager->applyCurrentPreset();
			// Implicitly enable the role system
			$permissionManager->enableRoleSystem();
		} );
	}
}
