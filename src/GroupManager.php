<?php

namespace BlueSpice\PermissionManager;

use BlueSpice\PermissionManager\Logging\GroupManagerSpecialLogLogger;
use Exception;
use InvalidArgumentException;
use MediaWiki\Config\Config;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Message\Message;
use MediaWiki\Permissions\Authority;
use MediaWiki\User\User;
use MWStake\MediaWiki\Component\DynamicConfig\DynamicConfigManager;
use Psr\Log\LoggerInterface;
use Wikimedia\Rdbms\ILoadBalancer;

class GroupManager {

	/** @var DynamicConfigManager */
	private DynamicConfigManager $configManager;
	/** @var ILoadBalancer */
	private ILoadBalancer $lb;
	/** @var Config */
	private Config $config;
	/** @var HookContainer */
	private HookContainer $hookContainer;
	/** @var LoggerInterface */
	private LoggerInterface $logger;
	/** @var GroupManagerSpecialLogLogger */
	private GroupManagerSpecialLogLogger $spLogger;

	/**
	 * @param DynamicConfigManager $configManager
	 * @param ILoadBalancer $lb
	 * @param Config $config Main config (wg)
	 * @param HookContainer $hookContainer
	 * @param LoggerInterface $logger
	 * @param GroupManagerSpecialLogLogger $spLogger
	 */
	public function __construct(
		DynamicConfigManager $configManager, ILoadBalancer $lb, Config $config,
		HookContainer $hookContainer, LoggerInterface $logger, GroupManagerSpecialLogLogger $spLogger
	) {
		$this->configManager = $configManager;
		$this->lb = $lb;
		$this->config = $config;
		$this->hookContainer = $hookContainer;
		$this->logger = $logger;
		$this->spLogger = $spLogger;
	}

	/**
	 * @param string $name
	 * @param Authority $actor
	 * @return void
	 * @throws Exception
	 */
	public function addGroup( string $name, Authority $actor ) {
		$this->assertActorCan( 'add', $actor );
		$this->assertValidName( $name );
		$current = $this->config->get( 'AdditionalGroups' ) ?? [];
		$current[$name] = true;
		$this->store( $current );
		$this->hookContainer->run( 'BSPermissionManagerGroupAdded', [ $name, $actor ] );
		$this->log( 'create', $actor, [ 'group' => $name ] );
	}

	/**
	 * @param string $oldName
	 * @param string $newName
	 * @param Authority $actor
	 * @return void
	 * @throws Exception
	 */
	public function editGroup( string $oldName, string $newName, Authority $actor ) {
		$this->assertActorCan( 'edit', $actor );
		$this->assertValidName( $newName );
		$this->assertGroupExists( $oldName );
		$current = $this->config->get( 'AdditionalGroups' ) ?? [];
		unset( $current[$oldName] );
		$current[$newName] = true;
		$this->renameGroup( $oldName, $newName );
		$this->store( $current );
		$this->hookContainer->run( 'BSPermissionManagerGroupEdited', [ $oldName, $newName, $actor ] );
		$this->log( 'modify', $actor, [ 'group' => $oldName, 'newGroup' => $newName ] );
	}

	/**
	 * @param string $name
	 * @param Authority $actor
	 * @return void
	 * @throws Exception
	 */
	public function removeGroup( string $name, Authority $actor ) {
		$this->assertActorCan( 'delete', $actor );
		$this->assertGroupExists( $name );
		$current = $this->config->get( 'AdditionalGroups' ) ?? [];
		unset( $current[$name] );
		$this->store( $current );
		$this->unassignUsers( $name );
		$this->hookContainer->run( 'BSPermissionManagerGroupDeleted', [ $name, $actor ] );
		$this->log( 'remove', $actor, [ 'group' => $name ] );
	}

	/**
	 * @param string $name
	 * @return void
	 */
	private function assertValidName( string $name ) {
		$invalidChars = [];
		$name = trim( $name );
		if ( substr_count( $name, '\'' ) > 0 ) {
			$invalidChars[] = '\'';
		}
		if ( substr_count( $name, '"' ) > 0 ) {
			$invalidChars[] = '"';
		}
		if ( !empty( $invalidChars ) ) {
			throw new InvalidArgumentException(
				Message::newFromKey( 'bs-permissionmanager-groupmanager-invalid-name' )
					->numParams( count( $invalidChars ) )
					->params( implode( ',', $invalidChars ) )
					->text()
			);
		} elseif ( preg_match( "/^[0-9]+$/", $name ) ) {
			throw new InvalidArgumentException(
				Message::newFromKey( 'bs-permissionmanager-groupmanager-invalid-name-numeric' )->plain()
			);
		} elseif ( strlen( $name ) > 255 ) {
			throw new InvalidArgumentException(
				Message::newFromKey( 'bs-permissionmanager-groupmanager-invalid-name-length' )->plain()
			);
		}
		if ( $this->checkGroupExists( $name ) ) {
			throw new InvalidArgumentException(
				Message::newFromKey( 'bs-permissionmanager-group-already-exists' )->plain()
			);
		}
	}

	/**
	 * @param array $value
	 * @return void
	 * @throws Exception
	 */
	private function store( array $value ) {
		$config = $this->configManager->getConfigObject( 'bs-groupmanager-groups' );
		if ( !$config ) {
			throw new Exception( 'Config object not found' );
		}
		$this->configManager->storeConfig( $config, $value );
	}

	/**
	 * @param string $oldName
	 * @param string $newName
	 * @return void
	 * @throws Exception
	 */
	private function renameGroup( string $oldName, string $newName ) {
		$db = $this->lb->getConnection( DB_PRIMARY );
		$res = $db->update(
			'user_groups',
			[ 'ug_group' => $newName ],
			[ 'ug_group' => $oldName ],
			__METHOD__
		);
		if ( !$res ) {
			throw new Exception( $db->lastError() );
		}
	}

	/**
	 * @param string $name
	 * @return void
	 */
	private function assertGroupExists( string $name ) {
		if ( !$this->checkGroupExists( $name ) ) {
			throw new InvalidArgumentException(
				Message::newFromKey( 'bs-permissionmanager-group-not-found' )->plain()
			);
		}
	}

	/**
	 * @param string $action
	 * @param Authority $actor
	 * @return void
	 */
	private function assertActorCan( string $action, Authority $actor ) {
		if ( $actor instanceof User && $actor->isSystemUser() ) {
			return;
		}
		if ( !$actor->isAllowed( 'wikiadmin' ) ) {
			throw new InvalidArgumentException(
				Message::newFromKey( 'bs-permissionmanager-action-not-allowed' )->plain()
			);
		}
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	private function checkGroupExists( string $name ): bool {
		$groupPermissions = $this->config->get( 'GroupPermissions' ) ?? [];
		$existingGroups = array_keys( $groupPermissions );
		return in_array( $name, $existingGroups );
	}

	/**
	 * @param string $type
	 * @param Authority $actor
	 * @param array $params
	 * @return void
	 */
	private function log( string $type, Authority $actor, array $params ) {
		// Special:Log logging
		$this->spLogger->log( $type, $actor, $params );
		// Structured logging
		$this->logger->info( 'New group created', array_merge( [
			'actor' => $actor->getUser()->getName(),
		], $params ) );
	}

	/**
	 * @param string $name
	 * @return void
	 */
	private function unassignUsers( string $name ) {
		$db = $this->lb->getConnection( DB_PRIMARY );
		$db->delete(
			'user_groups',
			[ 'ug_group' => $name ],
			__METHOD__
		);
	}

}
