<?php

namespace BlueSpice\PermissionManager\Tests;

use BlueSpice\PermissionManager\Preset\CustomPreset;
use PHPUnit\Framework\TestCase;

class CustomPresetTest extends TestCase {

	/**
	 * @covers \BlueSpice\PermissionManager\Preset\CustomPreset::evaluateSettingsFile
	 */
	public function testFileEvaluation() {
		$preset = new CustomPreset( __DIR__ . '/data/pm-settings.php' );
		$data = $preset->evaluateSettingsFile();

		$this->assertEquals( [
			'bsgGroupRoles' => [
				'bureaucrat' => [ 'accountmanager' => true ],
				'*' => [ 'reader' => false ],
				'bot' => [ 'bot' => true ]
			],
			'bsgNamespaceRoleLockdown' => [
				NS_USER => [ 'reader' => [ 'user' ] ],
				NS_MAIN => [ 'user' => [ '*' ] ]
			]
		], $data );
	}
}
