<?php

namespace BlueSpice\PermissionManager\Tests;

use BlueSpice\PermissionManager\GroupManager;
use BlueSpice\PermissionManager\Logging\GroupManagerSpecialLogLogger;
use Config;
use Exception;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Permissions\Authority;
use MWStake\MediaWiki\Component\DynamicConfig\DynamicConfigManager;
use MWStake\MediaWiki\Component\DynamicConfig\GlobalsDynamicConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;

class GroupManagerTest extends TestCase {

	/**
	 * @param string $group
	 * @param bool $expectSuccess
	 * @return void
	 * @throws Exception
	 * @dataProvider provideAddData
	 * @covers \BlueSpice\PermissionManager\GroupManager::addGroup
	 */
	public function testAddGroup( string $group, bool $expectSuccess ) {
		$authority = $this->getAuthorityMock();
		$configMock = $this->getConfigMock();
		$dynamicConfigMock = $this->getDynamicConfigMock(
			$expectSuccess ? $this->compileConfigData( $group, $configMock ) : null
		);
		$lbMock = $this->getLBMock();
		if ( $expectSuccess ) {
			$hcMock = $this->getHookContainerMock( 'BSPermissionManagerGroupAdded', [ $group, $authority ] );
		} else {
			$hcMock = $this->getHookContainerMock();
		}
		$loggerMock = $this->getLoggerMock( $expectSuccess );
		$spLoggerMock = $this->getSpLoggerMock( $expectSuccess );

		$manager = new GroupManager( $dynamicConfigMock, $lbMock, $configMock, $hcMock, $loggerMock, $spLoggerMock );
		if ( !$expectSuccess ) {
			$this->expectException( Exception::class );
		}
		$manager->addGroup( $group, $authority );
	}

	/**
	 * @param string $new
	 * @param bool $expectSuccess
	 * @return void
	 * @throws Exception
	 * @dataProvider provideEditData
	 * @covers \BlueSpice\PermissionManager\GroupManager::editGroup
	 */
	public function testEditGroup( string $new, bool $expectSuccess ) {
		$old = 'Dummy';
		$authority = $this->getAuthorityMock();
		$configMock = $this->getConfigMock();
		$dynamicConfigMock = $this->getDynamicConfigMock(
			$expectSuccess ? [ $new => true ] : null
		);
		if ( $expectSuccess ) {
			$lbMock = $this->getLBMock( 'update', [
				'user_groups',
				[ 'ug_group' => $new ],
				[ 'ug_group' => $old ],
				GroupManager::class . '::renameGroup'
			] );
		} else {
			$lbMock = $this->getLBMock();
		}

		if ( $expectSuccess ) {
			$hcMock = $this->getHookContainerMock( 'BSPermissionManagerGroupEdited', [ $old, $new, $authority ] );
		} else {
			$hcMock = $this->getHookContainerMock();
		}
		$loggerMock = $this->getLoggerMock( $expectSuccess );
		$spLoggerMock = $this->getSpLoggerMock( $expectSuccess );

		$manager = new GroupManager( $dynamicConfigMock, $lbMock, $configMock, $hcMock, $loggerMock, $spLoggerMock );
		if ( !$expectSuccess ) {
			$this->expectException( Exception::class );
		}
		$manager->editGroup( $old, $new, $authority );
	}

	/**
	 * @return void
	 * @throws Exception
	 * @covers \BlueSpice\PermissionManager\GroupManager::removeGroup
	 */
	public function testRemoveGroup() {
		$authority = $this->getAuthorityMock();
		$configMock = $this->getConfigMock();
		$dynamicConfigMock = $this->getDynamicConfigMock( [] );
		$lbMock = $this->getLBMock( 'delete', [
			'user_groups',
			[ 'ug_group' => 'Dummy' ],
			GroupManager::class . '::unassignUsers'
		] );
		$hcMock = $this->getHookContainerMock( 'BSPermissionManagerGroupDeleted', [ 'Dummy', $authority ] );
		$loggerMock = $this->getLoggerMock( true );
		$spLoggerMock = $this->getSpLoggerMock( true );

		$manager = new GroupManager( $dynamicConfigMock, $lbMock, $configMock, $hcMock, $loggerMock, $spLoggerMock );
		$manager->removeGroup( 'Dummy', $authority );
	}

	/**
	 * @return array[]
	 */
	public function provideAddData(): array {
		// "old name" is always "Dummy"
		return [
			[ 'foo', true ],
			[ 'foo123', true ],
			// same name
			[ 'Dummy', false ]
		];
	}

	public function provideEditData(): array {
		return [
			[ 'foo', true ],
			[ 'Dummy"""', false ],
			[ 'foo123', true ],
		];
	}

	/**
	 * @return Config|MockObject
	 */
	private function getConfigMock() {
		$mock = $this->createMock( Config::class );
		$mock->method( 'get' )->willReturnCallback( static function ( $key ) {
			if ( $key === 'AdditionalGroups' ) {
				return [ 'Dummy' => true ];
			}
			if ( $key === 'GroupPermissions' ) {
				return [ '*' => [], 'sysop' => [], 'Dummy' => [] ];
			}
			return null;
		} );
		return $mock;
	}

	/**
	 * @param array|null $data
	 * @return DynamicConfigManager|MockObject
	 */
	private function getDynamicConfigMock( ?array $data ) {
		$configMock = $this->createMock( GlobalsDynamicConfig::class );
		$mock = $this->createMock( DynamicConfigManager::class );
		if ( is_array( $data ) ) {
			$mock->expects( $this->once() )
				->method( 'storeConfig' )
				->with( $configMock, $data );
		} else {
			$mock->expects( $this->never() )
				->method( 'storeConfig' );
		}
		$mock->method( 'getConfigObject' )->willReturnCallback( static function ( $key ) use ( $configMock ) {
			if ( $key === 'bs-groupmanager-groups' ) {
				return $configMock;
			}
			return null;
		} );
		return $mock;
	}

	/**
	 * @param string|null $method
	 * @param array $data
	 * @return ILoadBalancer|MockObject
	 */
	private function getLBMock( ?string $method = null, array $data = [] ) {
		$dbMock = $this->createMock( IDatabase::class );
		$mock = $this->createMock( ILoadBalancer::class );
		if ( $method !== null ) {
			$dbMock->expects( $this->once() )
				->method( $method )
				->with( ...$data )
				->willReturn( true );
		}
		$mock->method( 'getConnection' )->willReturn( $dbMock );
		return $mock;
	}

	/**
	 * @param string $group
	 * @param Config $config
	 * @return array[]
	 */
	private function compileConfigData( string $group, Config $config ): array {
		$groups = $config->get( 'AdditionalGroups' ) ?? [];
		$groups[$group] = true;
		return $groups;
	}

	private function getHookContainerMock( string $hook = '', array $args = [] ) {
		$mock = $this->createMock( HookContainer::class );
		if ( $hook ) {
			$mock->expects( $this->once() )
				->method( 'run' )
				->with( $hook, $args );
		} else {
			$mock->expects( $this->never() )
				->method( 'run' );
		}
		return $mock;
	}

	/**
	 * @return Authority|MockObject
	 */
	private function getAuthorityMock() {
		$mock = $this->createMock( Authority::class );
		$mock->expects( $this->once() )
			->method( 'isAllowed' )
			->willReturn( true );
		return $mock;
	}

	/**
	 * @param bool $expectSuccess
	 * @return LoggerInterface|MockObject
	 */
	private function getLoggerMock( bool $expectSuccess ) {
		$mock = $this->createMock( LoggerInterface::class );
		if ( $expectSuccess ) {
			$mock->expects( $this->once() )
				->method( 'info' );
		} else {
			$mock->expects( $this->never() )
				->method( 'log' );
		}
		return $mock;
	}

	/**
	 * @param bool $expectSuccess
	 * @return GroupManagerSpecialLogLogger|MockObject
	 */
	private function getSpLoggerMock( bool $expectSuccess ) {
		$mock = $this->createMock( GroupManagerSpecialLogLogger::class );
		if ( $expectSuccess ) {
			$mock->expects( $this->once() )
				->method( 'log' );
		} else {
			$mock->expects( $this->never() )
				->method( 'log' );
		}
		return $mock;
	}

}
